<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeviceLogController extends Controller
{
    /**
     * Receive a single log entry from a device.
     *
     * POST /api/devices/log
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id'        => 'required|string',
            'level'            => 'required|in:info,warning,error,critical',
            'event'            => 'required|string|max:255',
            'message'          => 'required|string|max:5000',
            'stack_trace'      => 'nullable|string|max:10000',
            'context'          => 'nullable|array',
            'device_timestamp' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $log = DeviceLog::create($validator->validated());

        // Also update device last_seen
        Device::where('device_id', $request->device_id)
              ->update(['last_seen' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Log recorded',
            'id'      => $log->id,
        ]);
    }

    /**
     * Receive a batch of log entries from a device.
     *
     * POST /api/devices/logs/batch
     */
    public function storeBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
            'logs'      => 'required|array|max:100',
            'logs.*.level'            => 'required|in:info,warning,error,critical',
            'logs.*.event'            => 'required|string|max:255',
            'logs.*.message'          => 'required|string|max:5000',
            'logs.*.stack_trace'      => 'nullable|string|max:10000',
            'logs.*.context'          => 'nullable|array',
            'logs.*.device_timestamp' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $deviceId = $request->device_id;
        $inserted = 0;

        foreach ($request->logs as $logData) {
            DeviceLog::create(array_merge($logData, ['device_id' => $deviceId]));
            $inserted++;
        }

        Device::where('device_id', $deviceId)
              ->update(['last_seen' => now()]);

        return response()->json([
            'success'  => true,
            'message'  => "$inserted logs recorded",
            'inserted' => $inserted,
        ]);
    }

    /**
     * Retrieve logs (for dashboard).
     *
     * GET /api/devices/logs
     */
    public function index(Request $request)
    {
        $query = DeviceLog::query()
            ->orderBy('created_at', 'desc');

        // Filters
        if ($request->has('device_id')) {
            $query->forDevice($request->device_id);
        }
        if ($request->has('level')) {
            $query->where('level', $request->level);
        }
        if ($request->boolean('errors_only')) {
            $query->errors();
        }

        $logs = $query->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'logs'    => $logs,
        ]);
    }
}
