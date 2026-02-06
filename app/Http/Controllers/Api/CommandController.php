<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class CommandController extends Controller
{
    private $messaging;

    public function __construct()
    {
        $credentialsPath = base_path(config('firebase.credentials'));

        // Smart search: If the configured file is missing, look for ANY firebase json in storage/app
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

        if (!file_exists($credentialsPath)) {
            throw new \RuntimeException("Firebase credentials file not found. We checked the config path AND searched storage/app. Please ensure your Firebase JSON file is in storage/app.");
        }

        $factory = (new Factory)
            ->withServiceAccount($credentialsPath);

        $this->messaging = $factory->createMessaging();
    }

    // Send command to ALL online devices
    public function sendToAll(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:play,pause,stop,open',
            'spotify_uri' => 'required_if:action,play,open|string',
            'message' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Get all online devices
        $devices = Device::where('status', 'online')->get();

        if ($devices->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No online devices available'
            ], 400);
        }

        // Collect all FCM tokens
        $tokens = $devices->pluck('fcm_token')->filter()->toArray();

        // Create the FCM message
        $message = CloudMessage::new()
            ->withData([
                'action' => $request->action,
                'spotify_uri' => $request->spotify_uri ?? '',
                'timestamp' => now()->timestamp,
                'command_id' => Str::uuid()->toString()
            ]);

        // Send multicast message
        $report = $this->messaging->sendMulticast($message, $tokens);

        // Update devices status
        Device::whereIn('id', $devices->pluck('id'))
            ->update(['status' => 'streaming', 'last_seen' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Command sent to devices',
            'stats' => [
                'total_devices' => count($tokens),
                'successful' => $report->successes()->count(),
                'failed' => $report->failures()->count()
            ]
        ]);
    }

    // Send command to specific device
    public function sendToDevice(Request $request, Device $device)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:play,pause,stop,open',
            'spotify_uri' => 'required_if:action,play,open|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $message = CloudMessage::withTarget('token', $device->fcm_token)
            ->withData([
                'action' => $request->action,
                'spotify_uri' => $request->spotify_uri ?? '',
                'timestamp' => now()->timestamp,
                'command_id' => Str::uuid()->toString()
            ]);

        try {
            $this->messaging->send($message);

            $device->update([
                'status' => 'streaming',
                'last_seen' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Command sent to device'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send command: ' . $e->getMessage()
            ], 500);
        }
    }

    // Send command to specific group of devices
    public function sendToGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_ids' => 'required|array',
            'device_ids.*' => 'exists:devices,id',
            'action' => 'required|in:play,pause,stop,open',
            'spotify_uri' => 'required_if:action,play,open|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $devices = Device::whereIn('id', $request->device_ids)->get();
        $tokens = $devices->pluck('fcm_token')->filter()->toArray();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid devices found'
            ], 400);
        }

        $message = CloudMessage::new()
            ->withData([
                'action' => $request->action,
                'spotify_uri' => $request->spotify_uri ?? '',
                'timestamp' => now()->timestamp,
                'command_id' => Str::uuid()->toString()
            ]);

        $report = $this->messaging->sendMulticast($message, $tokens);

        Device::whereIn('id', $devices->pluck('id'))
            ->update(['status' => 'streaming', 'last_seen' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Command sent to device group',
            'stats' => [
                'total_devices' => count($tokens),
                'successful' => $report->successes()->count(),
                'failed' => $report->failures()->count()
            ]
        ]);
    }
}
