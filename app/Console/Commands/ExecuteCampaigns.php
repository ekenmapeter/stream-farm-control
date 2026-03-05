<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
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
        \Illuminate\Support\Facades\Log::info("ExecuteCampaigns: Found " . $activeAssignments->count() . " active assignments.");

        $advanced = 0;

        foreach ($activeAssignments as $assignment) {
            try {
                $campaign = $assignment->campaign;
                $device = $assignment->device;

                if (!$campaign || !$device || !$device->fcm_token) {
                    $this->warn(" -> Assignment #{$assignment->id}: Skipping (Missing Device/Token/Campaign)");
                    continue;
                }

                $this->comment(" -> Processing Assignment #{$assignment->id} for Device: {$device->name}");

                // 1. Get/Refresh tracks
                $tracks = $campaign->tracks()->orderBy('position_order')->get();
                if ($tracks->isEmpty()) {
                    $this->warn("    ! No tracks found for campaign {$campaign->id}");
                    continue;
                }

                // 2. Stable Shuffle
                $assignedAt = $assignment->assigned_at ?? $assignment->created_at ?? now();
                $seed = "{$assignment->device_id}_{$assignment->campaign_id}_{$assignedAt->timestamp}";
                $shuffledTracks = $tracks->sortBy(fn($t) => md5($seed . $t->id))->values();
                $trackCount = $shuffledTracks->count();

                // 3. Find current position
                $currentIndex = $shuffledTracks->search(function ($t) use ($assignment) {
                    return ($assignment->campaign_track_id && $t->id === $assignment->campaign_track_id) || $t->media_url === $assignment->media_url;
                });

                if ($currentIndex === false) {
                    $this->warn("    ! Current track not in list. Resetting to start.");
                    $currentIndex = $trackCount - 1; // Loops to 0 below
                }

                // 4. Timing Check
                $playedSeconds = now()->diffInSeconds($assignment->started_at);
                $track = $shuffledTracks->get($currentIndex);
                $duration = $track ? ($track->duration_seconds ?? 180) : 180;
                $buffer = rand(2, 8);
                $threshold = $duration + $buffer;

                $this->line("    * Track: " . Str::limit($assignment->media_url, 30) . " | Played: {$playedSeconds}s | Target: {$threshold}s");

                if (!$force && $playedSeconds < $threshold) {
                    $this->line("      - Not time to advance yet (" . ($threshold - $playedSeconds) . "s remaining).");
                    continue;
                }

                // 5. Determine Next Track
                $nextIndex = ($currentIndex < $trackCount - 1) ? $currentIndex + 1 : 0;
                $nextTrack = $shuffledTracks[$nextIndex];

                $this->info("    >>> ADVANCING to Track #" . ($nextIndex + 1) . ": {$nextTrack->media_url}");

                // 6. Send Command
                $command = $campaign->platform === 'spotify' ? 'play_spotify' : 'play_youtube';
                $message = CloudMessage::withTarget('token', $device->fcm_token)
                    ->withData([
                        'command'       => $command,
                        'track_id'      => $nextTrack->media_url,
                        'youtube_url'   => $nextTrack->media_url,
                        'media_url'     => $nextTrack->media_url,
                        'track_title'   => $nextTrack->media_title ?? '',
                        'action'        => 'play',
                        'platform'      => $campaign->platform,
                        'assignment_id' => (string) $assignment->id,
                        'timestamp'     => (string) now()->timestamp,
                        'command_id'    => Str::uuid()->toString(),
                    ])
                    ->withAndroidConfig(['priority' => 'high', 'ttl' => '3600s']);

                $messaging->send($message);

                // 7. Update Database
                $assignment->update([
                    'campaign_track_id' => $nextTrack->id,
                    'media_url'         => $nextTrack->media_url,
                    'media_title'       => $nextTrack->media_title ?? $campaign->name . ' - Track ' . ($nextIndex + 1),
                    'started_at'        => now(),
                ]);

                $device->update(['last_seen' => now()]);
                $advanced++;
                
                $this->info("    + Database Updated Successfully.");

            } catch (\Exception $e) {
                $this->error("    ! ERROR: " . $e->getMessage());
                \Illuminate\Support\Facades\Log::error("ExecuteCampaigns Error (Asgn #{$assignment->id}): " . $e->getMessage());
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
