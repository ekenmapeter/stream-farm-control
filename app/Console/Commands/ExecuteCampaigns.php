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
    protected $signature = 'campaigns:execute';
    protected $description = 'Advance campaign tracks on devices. Run every minute via cron.';

    public function handle()
    {
        // Adopt the pattern used in CommandController to resolve Firebase since container binding isn't available
        $messaging = $this->getMessaging();
        
        if (!$messaging) {
            $this->error("Failed to initialize Firebase Messaging. Check credentials.");
            return 1;
        }

        // Find all active campaign assignments that have been playing long enough
        $activeAssignments = DeviceAssignment::with(['device', 'campaign.tracks', 'campaignTrack'])
            ->whereNotNull('campaign_id')
            ->whereNotNull('campaign_track_id')
            ->where('status', 'playing')
            ->whereNotNull('started_at')
            ->get();

        // Log count for debugging
        \Illuminate\Support\Facades\Log::info("ExecuteCampaigns: Found " . $activeAssignments->count() . " assignments in 'playing' status.");

        $advanced = 0;

        foreach ($activeAssignments as $assignment) {
            try {
                $track = $assignment->campaignTrack;
                $campaign = $assignment->campaign;
                $device = $assignment->device;

                if (!$campaign || !$device || !$device->fcm_token) {
                    continue;
                }

                // Get all tracks for this campaign
                $tracks = $campaign->tracks()->orderBy('position_order')->get();
                if ($tracks->isEmpty()) {
                    $this->warn("Device {$device->name}: Campaign {$campaign->id} has no tracks.");
                    continue;
                }

                // Stable Shuffle: reproduces the exact sequence decided at deployment
                $assignedAt = $assignment->assigned_at ?? $assignment->created_at ?? now();
                $seed = "{$assignment->device_id}_{$assignment->campaign_id}_{$assignedAt->timestamp}";
                $shuffledTracks = $tracks->sortBy(fn($t) => md5($seed . $t->id))->values();

                // Determine current track index in HIS shuffled list
                $currentIndex = $shuffledTracks->search(function ($t) use ($assignment) {
                    return ($assignment->campaign_track_id && $t->id === $assignment->campaign_track_id) || $t->media_url === $assignment->media_url;
                });

                // Fallback: start fresh if current track not found
                if ($currentIndex === false) {
                    $this->warn("Device {$device->name}: Current track position lost. Resetting sequence.");
                    $currentIndex = $shuffledTracks->count() - 1;
                }

                // Check timing
                $playedSeconds = now()->diffInSeconds($assignment->started_at ?? now()->subDays(1));
                // Get duration of the track we JUST finished (if available)
                $trackInList = $shuffledTracks->get($currentIndex);
                $duration = $trackInList ? ($trackInList->duration_seconds ?? 180) : 180;

                // Buffer
                $buffer = rand(2, 8);

                if ($playedSeconds < ($duration + $buffer)) {
                    // Not time yet
                    continue; 
                }

                // Advance to next index in the shuffled list
                $nextIndex = ($currentIndex < $shuffledTracks->count() - 1)
                    ? $currentIndex + 1
                    : 0;

                $nextTrack = $shuffledTracks[$nextIndex];

                // Send FCM command for the next track
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
                    ->withAndroidConfig(['priority' => 'high', 'ttl' => '3600s'])
                    ->withApnsConfig([
                        'headers' => ['apns-priority' => '10', 'apns-push-type' => 'background'],
                        'payload' => ['aps' => ['content-available' => 1]]
                    ]);

                $messaging->send($message);

                // Update assignment to point to the new track
                $assignment->update([
                    'campaign_track_id' => $nextTrack->id,
                    'media_url'         => $nextTrack->media_url,
                    'media_title'       => $nextTrack->media_title ?? $campaign->name . ' - Track ' . ($nextIndex + 1),
                    'started_at'        => now(),
                ]);

                $device->update(['last_seen' => now()]);
                $advanced++;

                $this->info("Device {$device->name}: Advanced to track " . ($nextIndex + 1) . " - {$nextTrack->media_url}");
                
                // Dashboard Logging
                DeviceLog::create([
                    'device_id' => $device->id,
                    'level'     => 'info',
                    'message'   => "Campaign Advance: Switched to Track " . ($nextIndex + 1) . " (" . ($nextTrack->media_title ?? 'Unnamed') . ")",
                    'metadata'  => ['campaign_id'=>$campaign->id, 'track_url'=>$nextTrack->media_url, 'track_index'=>$nextIndex]
                ]);

            } catch (\Exception $e) {
                $this->error("Error processing assignment #{$assignment->id}: {$e->getMessage()}");
                
                if (isset($device)) {
                    DeviceLog::create([
                        'device_id' => $device->id,
                        'level'     => 'error',
                        'message'   => "Campaign Error: " . $e->getMessage(),
                        'metadata'  => ['campaign_id' => $campaign->id ?? null]
                    ]);
                }
            }
        }

        if ($advanced > 0) {
            $this->info("Campaign execution complete. Advanced {$advanced} device(s).");
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
