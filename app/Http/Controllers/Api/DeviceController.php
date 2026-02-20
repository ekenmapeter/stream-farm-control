<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class DeviceController extends Controller
{
    // Register a new device (called from Flutter app)
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
            'fcm_token' => 'required|string',
            'name' => 'nullable|string',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if device exists by ID or by Token (include soft-deleted ones)
        $device = Device::withTrashed()
                      ->where('device_id', $request->device_id)
                      ->orWhere('fcm_token', $request->fcm_token)
                      ->first();

        if ($device) {
            // Restore if it was deleted
            if ($device->trashed()) {
                $device->restore();
            }
            
            // Update existing device
            $device->update([
                'fcm_token' => $request->fcm_token,
                'name' => $request->name ?? $device->name,
                'metadata' => $request->metadata ?? $device->metadata,
                'status' => 'online',
                'last_seen' => now()
            ]);
        } else {
            // Create new device
            $device = Device::create([
                'device_id' => $request->device_id,
                'fcm_token' => $request->fcm_token,
                'name' => $request->name ?? 'Phone ' . Str::random(6),
                'metadata' => $request->metadata ?? [],
                'status' => 'online',
                'last_seen' => now()
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Device registered successfully',
            'device' => $device
        ]);
    }

    // Device heartbeat (update last_seen)
    public function heartbeat(Device $device)
    {
        $device->update([
            'last_seen' => now(),
            'status' => 'online'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Heartbeat received'
        ]);
    }

    // List all devices
    public function index()
    {
        $devices = Device::orderBy('status')
            ->orderBy('last_seen', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'devices' => $devices
        ]);
    }

    // Remove a device
    public function destroy(Device $device)
    {
        $device->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device removed successfully'
        ]);
    }
}
