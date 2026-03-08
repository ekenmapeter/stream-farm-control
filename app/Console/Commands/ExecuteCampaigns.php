<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\DeviceAssignment;
use App\Models\CampaignTrack;
use App\Models\DeviceLog;
use Illuminate\Support\Str;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Factory;

class ExecuteCampaigns extends Command
{
    protected $signature = 'campaigns:execute {--force : Skip duration checks and advance immediately}';
    protected $description = 'Advance campaign tracks on devices. Run every minute via cron.';

    public function handle()
    {
        $force = $this->option('force');
        $messaging = $this->getMessaging();

        if (!$messaging) {
            $this->error("CRITICAL: Firebase Messaging not initialized. Check credentials.");
            return 1;
        }

        $activeAssignments = DeviceAssignment::with(['device', 'campaign.tracks', 'campaignTrack'])
            ->whereNotNull('campaign_id')
            ->whereNotNull('campaign_track_id')
            ->where('status', 'playing')
            ->whereNotNull('started_at')
            ->get();

        $this->info("[" . now()->toDateTimeString() . "] ExecuteCampaigns: Found " . $activeAssignments->count() . " active playing assignments.");
        Log::info("ExecuteCampaigns: Found " . $activeAssignments->count() . " active assignments.");

        $advanced = 0;

        foreach ($activeAssignments as $assignment) {
            // Use a database transaction to ensure consistency
            DB::beginTransaction();
            try {
                $assignment->refresh();
                $campaign = $assignment->campaign;
                $device = $assignment->device;

                if (!$campaign || !$device || !$device->fcm_token) {
                    $msg = "Asgn #{$assignment->id}: Skipping (Missing Device/Token/Campaign)";
                    $this->warn($msg);
                    Log::info($msg);
                    DB::rollBack();
                    continue;
                }

                // Get/Refresh tracks
                $tracks = $campaign->tracks()->orderBy('position_order')->get();
                if ($tracks->isEmpty()) {
                    $msg = "Asgn #{$assignment->id}: Campaign has no tracks.";
                    $this->warn($msg);
                    Log::info($msg);
                    DB::rollBack();
                    continue;
                }

                // Stable Shuffle
                $assignedAt = $assignment->assigned_at ?? $assignment->created_at ?? now();
                $seed = "{$assignment->device_id}_{$assignment->campaign_id}_" . (int)$assignedAt->timestamp;
                $shuffledTracks = $tracks->sortBy(fn($t) => md5($seed . $t->id))->values();
                $trackCount = $shuffledTracks->count();

                // Find current position
                $currentIndex = $shuffledTracks->search(function ($t) use ($assignment) {
                    return ($assignment->campaign_track_id && (int)$t->id === (int)$assignment->campaign_track_id)
                        || $t->media_url === $assignment->media_url;
                });

                if ($currentIndex === false) {
                    $msg = "Asgn #{$assignment->id}: Track position lost. Resetting to first track.";
                    $this->warn($msg);
                    Log::info($msg);
                    $currentIndex = 0;
                }

                // Timing Check
                $startedAt = ($assignment->started_at ?? $assignment->created_at ?? now())->timezone('UTC');
                $currentTime = now()->timezone('UTC');
                $playedSeconds = $currentTime->diffInSeconds($startedAt);

                $track = $shuffledTracks->get($currentIndex);
                // Use the track's actual duration_seconds (default 180s if not set).
                // Add a small 2s grace period to account for cron scheduling jitter.
                $duration  = $track ? (int)($track->duration_seconds ?? 180) : 180;
                $threshold = $duration + 2;

                $msg = "Asgn #{$assignment->id}: Track '{$assignment->media_url}' | Played: {$playedSeconds}s | Goal: {$threshold}s | Start(UTC): " . $startedAt->toDateTimeString();
                $this->line($msg);
                Log::info($msg);

                if (!$force && $playedSeconds < $threshold) {
                    $this->line("      - Not time yet.");
                    DB::rollBack();
                    continue;
                }

                // Determine Next Track
                $nextIndex = ($currentIndex < $trackCount - 1) ? $currentIndex + 1 : 0;
                $nextTrack = $shuffledTracks[$nextIndex];

                $msg = "Asgn #{$assignment->id}: >>> ADVANCING to Track #" . ($nextIndex + 1) . ": {$nextTrack->media_url}";
                $this->info($msg);
                Log::info($msg);

                // Send Command
                $command = $campaign->platform === 'spotify' ? 'play_spotify' : 'play_youtube';

                $message = CloudMessage::withTarget('token', $device->fcm_token)
                    ->withData([
                        'command'          => $command,
                        'track_id'         => (string)$nextTrack->media_url,
                        'youtube_url'      => (string)$nextTrack->media_url,
                        'media_url'        => (string)$nextTrack->media_url,
                        'duration_seconds' => (string)($nextTrack->duration_seconds ?? 180),
                        'action'           => 'play',
                        'platform'         => (string)$campaign->platform,
                        'assignment_id'    => (string)$assignment->id,
                        'timestamp'        => (string)now()->timestamp,
                        'command_id'       => Str::uuid()->toString(),
                    ])
                    ->withAndroidConfig(['priority' => 'high', 'ttl' => '3600s'])
                    ->withApnsConfig([
                        'headers' => ['apns-priority' => '10', 'apns-push-type' => 'background'],
                        'payload' => ['aps' => ['content-available' => 1]]
                    ]);

                try {
                    $messaging->send($message);
                } catch (\Kreait\Firebase\Exception\MessagingException $e) {
                    Log::error("Asgn #{$assignment->id}: Firebase send failed: " . $e->getMessage());
                    $this->error("Firebase send failed for assignment {$assignment->id}");
                    DB::rollBack();
                    continue;
                }

                // Update Database
                $assignment->update([
                    'campaign_track_id' => (int)$nextTrack->id,
                    'media_url'         => (string)$nextTrack->media_url,
                    'media_title'       => (string)($nextTrack->media_title ?? $campaign->name . ' - Track ' . ($nextIndex + 1)),
                    'started_at'        => now()->timezone('UTC'),
                ]);

                $device->update(['last_seen' => now()]);

                DB::commit();
                $advanced++;

                Log::info("Asgn #{$assignment->id}: Database Updated.");
            } catch (\Exception $e) {
                DB::rollBack();
                $msg = "Asgn #{$assignment->id} ERROR: " . $e->getMessage();
                $this->error($msg);
                Log::error($msg);
            }
        }

        if ($advanced > 0) {
            $this->info("Done! Successfully advanced {$advanced} device(s).");
        } else {
            $this->comment("Process finished. No tracks were advanced in this run.");
        }

        return 0;
    }

    private function getMessaging()
    {
        $credentialsPath = base_path(config('firebase.credentials'));

        if (!file_exists($credentialsPath)) {
            $storagePath = storage_path('app');
            $files = glob($storagePath . '/*.json');
            foreach ($files as $file) {
                if (str_contains($file, 'firebase-adminsdk')) {
                    $credentialsPath = $file;
                    break;
                }
            }
        }

        if (!file_exists($credentialsPath)) return null;

        try {
            return (new Factory)->withServiceAccount($credentialsPath)->createMessaging();
        } catch (\Exception $e) {
            return null;
        }
    }
}
