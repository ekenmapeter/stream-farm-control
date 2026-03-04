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

        $advanced = 0;

        foreach ($activeAssignments as $assignment) {
            $track = $assignment->campaignTrack;
            $campaign = $assignment->campaign;
            $device = $assignment->device;

            if (!$track || !$campaign || !$device || !$device->fcm_token) {
                continue;
            }

            // Check if the track has been playing longer than its duration
            $playedSeconds = now()->diffInSeconds($assignment->started_at);
            $duration = $track->duration_seconds ?? 180;

            // Add a small random buffer (0-20 seconds) to look more natural
            $buffer = rand(0, 20);

            if ($playedSeconds < ($duration + $buffer)) {
                continue; // Not time yet
            }

            // Get all tracks for this campaign in order
            $tracks = $campaign->tracks()->orderBy('position_order')->get();
            if ($tracks->isEmpty()) continue;

            // Find the next track (loop back to first if at the end)
            $currentIndex = $tracks->search(function ($t) use ($track) {
                return $t->id === $track->id;
            });

            $nextIndex = ($currentIndex !== false && $currentIndex < $tracks->count() - 1)
                ? $currentIndex + 1
                : 0; // Loop back to beginning

            $nextTrack = $tracks[$nextIndex];

            // Send FCM command for the next track
            try {
                $command = $campaign->platform === 'spotify' ? 'play_spotify' : 'play_youtube';

                $message = CloudMessage::withTarget('token', $device->fcm_token)
                    ->withData([
                        'command'       => $command,
                        'track_id'      => $nextTrack->media_url,
                        'youtube_url'   => $nextTrack->media_url,
                        'media_url'     => $nextTrack->media_url,
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
                
                // Log to Dashboard
                DeviceLog::create([
                    'device_id' => $device->id,
                    'level'     => 'info',
                    'message'   => "Campaign Advance: Switched to Track " . ($nextIndex + 1) . " - " . ($nextTrack->media_title ?? 'Unnamed'),
                    'metadata'  => ['campaign_id'=>$campaign->id, 'track_url'=>$nextTrack->media_url]
                ]);

            } catch (\Exception $e) {
                $this->error("Device {$device->name}: Failed to advance - {$e->getMessage()}");
                
                // Log Error to Dashboard
                DeviceLog::create([
                    'device_id' => $device->id,
                    'level'     => 'error',
                    'message'   => "Campaign Error: Failed to advance to next track: " . $e->getMessage(),
                    'metadata'  => ['campaign_id'=>$campaign->id]
                ]);
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
