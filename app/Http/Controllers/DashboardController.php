<?php
namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\CommandController;

class DashboardController extends Controller
{
    public function index()
    {
        // Get all devices
        $devices = Device::orderBy('status', 'desc')
                         ->orderBy('last_seen', 'desc')
                         ->get();

        // Calculate counts
        $onlineCount = Device::whereIn('status', ['online', 'streaming'])->count();
        $streamingCount = Device::where('status', 'streaming')->count();
        $offlineCount = Device::where('status', 'offline')->count();
        $totalCount = $devices->count();

        // Recent logs (last 24h)
        $recentLogs = DeviceLog::with('device')
            ->orderBy('created_at', 'desc')
            ->where('created_at', '>=', now()->subHours(24))
            ->limit(100)
            ->get();

        // Error count (last 24h)
        $errorCount = DeviceLog::errors()
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        // Pass data to the view
        return view('dashboard', [
            'devices'        => $devices,
            'onlineCount'    => $onlineCount,
            'streamingCount' => $streamingCount,
            'offlineCount'   => $offlineCount,
            'totalCount'     => $totalCount,
            'recentLogs'     => $recentLogs,
            'errorCount'     => $errorCount,
        ]);
    }

    public function sendCommand(Request $request)
    {
        // Simple validation
        $request->validate([
            'action' => 'required|in:play,pause,stop,open',
            'platform' => 'nullable|in:spotify,youtube',
            'spotify_uri' => 'nullable|string',
            'youtube_url' => 'nullable|string',
            'media_url' => 'nullable|string'
        ]);

        // Use CommandController to send the message
        $commandController = app()->make(CommandController::class);
        $response = $commandController->sendToAll($request);

        if ($response->getStatusCode() === 200) {
            return back()->with('success', 'Command broadcasted to all devices!');
        } else {
            return back()->with('error', 'Failed to send command: ' . (json_decode($response->getContent())->message ?? 'Unknown error'));
        }
    }
}
