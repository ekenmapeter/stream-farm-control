<?php
namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Get all devices
        $devices = Device::orderBy('status', 'desc')
                         ->orderBy('last_seen', 'desc')
                         ->get();

        // Calculate counts
        $onlineCount = Device::where('status', 'online')->count();
        $streamingCount = Device::where('status', 'streaming')->count();
        $offlineCount = Device::where('status', 'offline')->count();
        $totalCount = $devices->count();

        // Pass data to the view
        return view('dashboard', [
            'devices' => $devices,
            'onlineCount' => $onlineCount,
            'streamingCount' => $streamingCount,
            'offlineCount' => $offlineCount,
            'totalCount' => $totalCount
        ]);
    }

    public function sendCommand(Request $request)
    {
        // Simple validation
        $request->validate([
            'action' => 'required|in:play,pause,stop,open',
            'spotify_uri' => 'required_if:action,play,open|string'
        ]);

        // Use CommandController to send the message
        $commandController = app()->make(Api\CommandController::class);
        $response = $commandController->sendToAll($request);

        if ($response->getStatusCode() === 200) {
            return back()->with('success', 'Command broadcasted to all devices!');
        } else {
            return back()->with('error', 'Failed to send command: ' . (json_decode($response->getContent())->message ?? 'Unknown error'));
        }
    }
}
