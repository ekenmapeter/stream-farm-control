<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stream Farm Dashboard</title>
    <!-- Tailwind CSS via CDN (for development) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {'50': '#eff6ff', '100': '#dbeafe', '200': '#bfdbfe', '300': '#93c5fd', '400': '#60a5fa', '500': '#3b82f6', '600': '#2563eb', '700': '#1d4ed8', '800': '#1e40af', '900': '#1e3a8a', '950': '#172554'},
                    }
                }
            }
        }
    </script>
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .glass-card { backdrop-filter: blur(10px); }
        .smooth-transition { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .stat-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen text-gray-800">

    <!-- Top Navigation -->
    <nav class="bg-white/80 glass-card border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <div class="bg-gradient-to-r from-primary-600 to-blue-500 p-2 rounded-lg">
                        <i class="fas fa-satellite-dish text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-primary-700 to-gray-900 bg-clip-text text-transparent">
                            Stream Farm Control
                        </h1>
                        <p class="text-xs text-gray-500">Managing 50 devices in real-time</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="document.getElementById('manualRegisterModal').classList.remove('hidden')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg smooth-transition font-medium text-sm border border-slate-200">
                        <i class="fas fa-plus mr-2"></i>Manual Register
                    </button>
                    <button class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg smooth-transition font-medium text-sm">
                        <i class="fas fa-rocket mr-2"></i>Deploy Command
                    </button>
                    <div class="relative group">
                        <button class="h-10 w-10 rounded-full bg-gradient-to-r from-cyan-500 to-primary-500 flex items-center justify-center text-white font-bold shadow-lg hover:ring-4 hover:ring-primary-100 transition-all">
                            {{ substr(auth()->user()->name ?? 'AD', 0, 2) }}
                        </button>
                        <!-- Dropdown -->
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl py-2 invisible group-hover:visible opacity-0 group-hover:opacity-100 transition-all z-[60] border border-gray-100">
                            <div class="px-4 py-2 border-b border-gray-100">
                                <p class="text-xs text-gray-400">Signed in as</p>
                                <p class="text-sm font-bold text-gray-800 truncate">{{ auth()->user()->email }}</p>
                            </div>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-3 text-sm text-red-600 hover:bg-red-50 flex items-center transition-colors">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Alerts -->
        @if(session('success'))
            <div class="mb-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl flex items-center justify-between smooth-transition">
                <div class="flex items-center">
                    <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center mr-4">
                        <i class="fas fa-check-circle text-green-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-green-800">{{ session('success') }}</p>
                        <p class="text-sm text-green-600">Command executed successfully</p>
                    </div>
                </div>
                <button class="text-green-800 hover:text-green-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-gradient-to-r from-red-50 to-rose-50 border border-red-200 rounded-xl flex items-center justify-between smooth-transition">
                <div class="flex items-center">
                    <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center mr-4">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-red-800">{{ session('error') }}</p>
                        <p class="text-sm text-red-600">Please check your connection</p>
                    </div>
                </div>
                <button class="text-red-800 hover:text-red-900">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6 smooth-transition stat-card border border-gray-100 hover:shadow-xl">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Online Devices</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $onlineCount ?? 0 }}</p>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-gradient-to-r from-green-100 to-emerald-100 flex items-center justify-center">
                        <i class="fas fa-wifi text-green-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-green-500 to-emerald-400 rounded-full" style="width: {{ $onlineCount && $totalCount ? ($onlineCount/$totalCount*100) : 0 }}%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 smooth-transition stat-card border border-gray-100 hover:shadow-xl">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Streaming Now</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $streamingCount ?? 0 }}</p>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-gradient-to-r from-blue-100 to-cyan-100 flex items-center justify-center">
                        <i class="fas fa-music text-blue-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-blue-500 to-cyan-400 rounded-full" style="width: {{ $streamingCount && $totalCount ? ($streamingCount/$totalCount*100) : 0 }}%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 smooth-transition stat-card border border-gray-100 hover:shadow-xl">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Offline</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $offlineCount ?? 0 }}</p>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-gradient-to-r from-gray-100 to-slate-100 flex items-center justify-center">
                        <i class="fas fa-wifi-slash text-gray-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-gray-500 to-slate-400 rounded-full" style="width: {{ $offlineCount && $totalCount ? ($offlineCount/$totalCount*100) : 0 }}%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 smooth-transition stat-card border border-gray-100 hover:shadow-xl">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Devices</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $totalCount ?? 0 }}/50</p>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-gradient-to-r from-purple-100 to-violet-100 flex items-center justify-center">
                        <i class="fas fa-mobile-alt text-purple-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-purple-500 to-violet-400 rounded-full" style="width: {{ $totalCount ? ($totalCount/50*100) : 0 }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Error Count Card -->
            <div class="bg-white rounded-2xl shadow-lg p-6 smooth-transition stat-card border border-gray-100 hover:shadow-xl">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Errors (24h)</p>
                        <p class="text-3xl font-bold {{ ($errorCount ?? 0) > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $errorCount ?? 0 }}</p>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-gradient-to-r from-red-100 to-rose-100 flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-xs text-gray-500">Last 24 hours • <a href="#device-logs" class="text-primary-600 hover:underline">View logs ↓</a></p>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Command Panel -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                    <div class="bg-gradient-to-r from-primary-600 to-blue-500 px-6 py-4">
                        <h2 class="text-xl font-bold text-white flex items-center">
                            <i class="fas fa-broadcast-tower mr-3"></i> Broadcast Command
                        </h2>
                        <p class="text-primary-100 text-sm">Send actions to all connected devices</p>
                    </div>
                    <div class="p-6">
                        <form action="{{ route('send.command') }}" method="POST" id="commandForm">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-play-circle mr-2 text-primary-500"></i>Platform & Action
                                    </label>
                                    <div class="grid grid-cols-2 gap-3 mb-3">
                                        <label class="platform-btn flex items-center p-3 border rounded-xl cursor-pointer smooth-transition border-primary-500 bg-primary-50" data-platform="spotify">
                                            <input type="radio" name="platform" value="spotify" class="hidden" checked>
                                            <i class="fab fa-spotify text-green-500 mr-2"></i>
                                            <span class="font-medium">Spotify</span>
                                        </label>
                                        <label class="platform-btn flex items-center p-3 border rounded-xl cursor-pointer smooth-transition" data-platform="youtube">
                                            <input type="radio" name="platform" value="youtube" class="hidden">
                                            <i class="fab fa-youtube text-red-500 mr-2"></i>
                                            <span class="font-medium">YouTube</span>
                                        </label>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="flex items-center p-3 border rounded-xl cursor-pointer smooth-transition hover:border-primary-300 hover:bg-primary-50">
                                            <input type="radio" name="action" value="play" class="h-4 w-4 text-primary-600" checked>
                                            <div class="ml-2">
                                                <p class="text-sm font-medium text-gray-900">Play</p>
                                            </div>
                                        </label>
                                        <label class="flex items-center p-3 border rounded-xl cursor-pointer smooth-transition hover:border-primary-300 hover:bg-primary-50">
                                            <input type="radio" name="action" value="pause" class="h-4 w-4 text-primary-600">
                                            <div class="ml-2">
                                                <p class="text-sm font-medium text-gray-900">Pause</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div id="media-url-container">
                                    <div id="spotify-input-group">
                                        <label for="spotify_uri" class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fab fa-spotify mr-2 text-green-500"></i>Spotify URI
                                        </label>
                                        <div class="relative">
                                            <input type="text" id="spotify_uri" name="spotify_uri" value="spotify:track:4cOdK2wGLETKBW3PvgPWqT" class="w-full p-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 smooth-transition">
                                        </div>
                                        <p class="text-xs text-gray-500 mt-2">Example: spotify:track:xxx</p>
                                    </div>
                                    <div id="youtube-input-group" class="hidden">
                                        <label for="youtube_url" class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fab fa-youtube mr-2 text-red-500"></i>YouTube URL
                                        </label>
                                        <div class="relative">
                                            <input type="text" id="youtube_url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=..." class="w-full p-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 smooth-transition">
                                        </div>
                                        <p class="text-xs text-gray-500 mt-2">Example: https://youtu.be/xxx</p>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="w-full bg-gradient-to-r from-primary-600 to-blue-500 hover:from-primary-700 hover:to-blue-600 text-white font-semibold py-4 px-6 rounded-xl smooth-transition flex items-center justify-center shadow-lg hover:shadow-xl">
                                <i class="fas fa-satellite mr-3"></i> Broadcast to All Online Devices
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Quick Commands -->
                <div class="mt-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-bolt mr-2 text-amber-500"></i> Quick Actions
                    </h3>
                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                        <button class="quick-command p-4 bg-white border border-gray-200 rounded-xl text-center smooth-transition hover:shadow-md hover:border-green-300" data-action="play" data-platform="spotify" data-uri="spotify:track:4cOdK2wGLETKBW3PvgPWqT">
                            <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-2">
                                <i class="fab fa-spotify text-green-600"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-900">Spotify Hit</p>
                        </button>
                        <button class="quick-command p-4 bg-white border border-gray-200 rounded-xl text-center smooth-transition hover:shadow-md hover:border-red-300" data-action="play" data-platform="youtube" data-uri="https://www.youtube.com/watch?v=dQw4w9WgXcQ">
                            <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-2">
                                <i class="fab fa-youtube text-red-600"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-900">YouTube Hit</p>
                        </button>
                        <button class="quick-command p-4 bg-white border border-gray-200 rounded-xl text-center smooth-transition hover:shadow-md hover:border-blue-300" data-action="pause">
                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-2">
                                <i class="fas fa-pause text-blue-600"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-900">Pause All</p>
                        </button>
                        <button class="quick-command p-4 bg-white border border-gray-200 rounded-xl text-center smooth-transition hover:shadow-md hover:border-orange-300" data-action="stop">
                            <div class="h-10 w-10 rounded-full bg-orange-100 flex items-center justify-center mx-auto mb-2">
                                <i class="fas fa-stop text-orange-600"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-900">Stop All</p>
                        </button>
                        <button class="quick-command p-4 bg-gradient-to-r from-primary-600 to-blue-500 rounded-xl text-center smooth-transition shadow-lg text-white" data-action="open">
                            <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-2 text-white">
                                <i class="fas fa-external-link-alt"></i>
                            </div>
                            <p class="text-sm font-semibold">Wake App</p>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Devices Panel -->
            <div>
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100 h-full">
                    <div class="bg-gradient-to-r from-gray-800 to-slate-900 px-6 py-4">
                        <h2 class="text-xl font-bold text-white flex items-center">
                            <i class="fas fa-server mr-3"></i> Connected Devices
                            <span class="ml-auto bg-white/20 text-white text-sm font-medium px-3 py-1 rounded-full">
                                {{ $devices->count() ?? 0 }}
                            </span>
                        </h2>
                    </div>
                    <div class="p-4">
                        @if($devices && $devices->count() > 0)
                        <div class="space-y-4 max-h-[500px] overflow-y-auto pr-2">
                            @foreach($devices as $device)
                            <div class="p-4 border border-gray-200 rounded-xl smooth-transition hover:border-primary-300 hover:shadow-sm">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="flex items-center mb-2">
                                            @php
                                                $statusConfig = [
                                                    'online' => ['color' => 'text-green-600', 'bg' => 'bg-green-100', 'icon' => 'fa-wifi'],
                                                    'streaming' => ['color' => 'text-blue-600', 'bg' => 'bg-blue-100', 'icon' => 'fa-music'],
                                                    'offline' => ['color' => 'text-gray-600', 'bg' => 'bg-gray-100', 'icon' => 'fa-wifi-slash']
                                                ];
                                                $config = $statusConfig[$device->status] ?? $statusConfig['offline'];
                                            @endphp
                                            <div class="h-8 w-8 rounded-full {{ $config['bg'] }} flex items-center justify-center mr-3">
                                                <i class="fas {{ $config['icon'] }} {{ $config['color'] }} text-xs"></i>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900">{{ $device->name ?? 'Unnamed Device' }}</p>
                                                <p class="text-xs text-gray-500">{{ $device->device_id }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center text-sm text-gray-600 mt-3">
                                            <i class="far fa-clock mr-2"></i>
                                            @if($device->last_seen)
                                                <span>{{ $device->last_seen->diffForHumans() }}</span>
                                            @else
                                                <span>Never</span>
                                            @endif
                                            <span class="mx-2">•</span>
                                            <i class="fas fa-mobile-alt mr-2"></i>
                                            <span>
                                                @if($device->metadata && is_array($device->metadata))
                                                    {{ $device->metadata['model'] ?? 'Unknown' }}
                                                @else
                                                    Unknown
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button class="send-single h-9 w-9 rounded-lg bg-primary-50 text-primary-600 hover:bg-primary-100 smooth-transition flex items-center justify-center" data-device-id="{{ $device->id }}" title="Send play command">
                                            <i class="fas fa-play text-sm"></i>
                                        </button>
                                        <button class="remove-device h-9 w-9 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 smooth-transition flex items-center justify-center" data-device-id="{{ $device->id }}" title="Remove device">
                                            <i class="fas fa-trash-alt text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center py-12">
                            <div class="h-20 w-20 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-mobile-alt text-gray-400 text-3xl"></i>
                            </div>
                            <h4 class="text-lg font-medium text-gray-900 mb-2">No devices connected</h4>
                            <p class="text-gray-500 max-w-xs mx-auto">Install the Flutter app on your phones to see them appear here.</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- Device Activity Log (last 24h) -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <div class="mt-10" id="device-logs">
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                <div class="bg-gradient-to-r from-slate-800 to-gray-900 px-6 py-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-white flex items-center">
                            <i class="fas fa-scroll mr-3"></i> Device Activity Log
                        </h2>
                        <p class="text-gray-400 text-sm">Last 24 hours • {{ $recentLogs->count() ?? 0 }} entries</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <!-- Filter buttons -->
                        <button class="log-filter px-3 py-1.5 rounded-lg text-xs font-medium bg-white/20 text-white hover:bg-white/30 smooth-transition" data-level="all">All</button>
                        <button class="log-filter px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-500/30 text-blue-200 hover:bg-blue-500/50 smooth-transition" data-level="info">Info</button>
                        <button class="log-filter px-3 py-1.5 rounded-lg text-xs font-medium bg-amber-500/30 text-amber-200 hover:bg-amber-500/50 smooth-transition" data-level="warning">Warning</button>
                        <button class="log-filter px-3 py-1.5 rounded-lg text-xs font-medium bg-red-500/30 text-red-200 hover:bg-red-500/50 smooth-transition" data-level="error">Errors</button>
                    </div>
                </div>

                <div class="p-4">
                    @if($recentLogs && $recentLogs->count() > 0)
                    <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 bg-gray-50">
                                <tr class="text-left text-gray-500 border-b border-gray-200">
                                    <th class="py-3 px-3 font-medium">Level</th>
                                    <th class="py-3 px-3 font-medium">Device</th>
                                    <th class="py-3 px-3 font-medium">Event</th>
                                    <th class="py-3 px-3 font-medium">Message</th>
                                    <th class="py-3 px-3 font-medium">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentLogs as $log)
                                @php
                                    $levelConfig = [
                                        'info'     => ['badge' => 'bg-blue-100 text-blue-700',   'icon' => 'fa-info-circle text-blue-500'],
                                        'warning'  => ['badge' => 'bg-amber-100 text-amber-700', 'icon' => 'fa-exclamation-triangle text-amber-500'],
                                        'error'    => ['badge' => 'bg-red-100 text-red-700',     'icon' => 'fa-times-circle text-red-500'],
                                        'critical' => ['badge' => 'bg-red-200 text-red-900',     'icon' => 'fa-skull-crossbones text-red-700'],
                                    ];
                                    $lc = $levelConfig[$log->level] ?? $levelConfig['info'];
                                @endphp
                                <tr class="log-row border-b border-gray-100 hover:bg-gray-50 smooth-transition {{ $log->stack_trace ? 'cursor-pointer' : '' }}"
                                    data-level="{{ $log->level }}"
                                    @if($log->stack_trace) onclick="this.nextElementSibling.classList.toggle('hidden')" @endif
                                >
                                    <td class="py-3 px-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $lc['badge'] }}">
                                            <i class="fas {{ $lc['icon'] }} mr-1.5 text-xs"></i>
                                            {{ ucfirst($log->level) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="text-gray-900 font-medium">{{ $log->device->name ?? 'Unknown' }}</span>
                                        <br>
                                        <span class="text-xs text-gray-400 font-mono">{{ Str::limit($log->device_id, 12) }}</span>
                                    </td>
                                    <td class="py-3 px-3">
                                        <code class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded text-xs font-mono">{{ $log->event }}</code>
                                    </td>
                                    <td class="py-3 px-3 text-gray-600 max-w-md truncate">
                                        {{ Str::limit($log->message, 80) }}
                                        @if($log->stack_trace)
                                            <i class="fas fa-chevron-down text-xs text-gray-400 ml-1"></i>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 text-gray-500 text-xs whitespace-nowrap">
                                        {{ $log->created_at->format('H:i:s') }}
                                        <br>
                                        <span class="text-gray-400">{{ $log->created_at->diffForHumans() }}</span>
                                    </td>
                                </tr>
                                @if($log->stack_trace)
                                <tr class="hidden">
                                    <td colspan="5" class="p-4 bg-gray-900">
                                        <div class="rounded-lg overflow-hidden">
                                            <div class="flex items-center justify-between px-4 py-2 bg-gray-800">
                                                <span class="text-xs text-gray-400 font-medium"><i class="fas fa-code mr-2"></i>Stack Trace</span>
                                                <button class="text-xs text-gray-400 hover:text-white" onclick="navigator.clipboard.writeText(this.closest('td').querySelector('pre').textContent); this.textContent='Copied!'">
                                                    <i class="far fa-copy mr-1"></i> Copy
                                                </button>
                                            </div>
                                            <pre class="p-4 text-xs text-green-400 font-mono overflow-x-auto max-h-48 overflow-y-auto leading-relaxed">{{ $log->stack_trace }}</pre>
                                        </div>
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-16">
                        <div class="h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-medium text-gray-900 mb-2">No activity yet</h4>
                        <p class="text-gray-500">Device logs will appear here once your Flutter apps start sending data.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </main>

    <!-- Manual Register Modal -->
    <div id="manualRegisterModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[100] flex items-center justify-center hidden">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-300">
            <div class="bg-slate-800 px-6 py-4 flex items-center justify-between">
                <h3 class="text-lg font-bold text-white"><i class="fas fa-plus-circle mr-2 text-primary-400"></i>Manual Device Registration</h3>
                <button onclick="document.getElementById('manualRegisterModal').classList.add('hidden')" class="text-slate-400 hover:text-white transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Display Name</label>
                        <input type="text" id="manual_name" placeholder="e.g. Pixel 6 Pro" class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Device ID</label>
                        <input type="text" id="manual_device_id" placeholder="Copy from Phone app Status tab" class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">FCM Token</label>
                        <textarea id="manual_fcm_token" rows="4" placeholder="Paste the long token here..." class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-all text-xs font-mono"></textarea>
                    </div>
                </div>
                <div class="mt-8 flex space-x-3">
                    <button onclick="document.getElementById('manualRegisterModal').classList.add('hidden')" class="flex-1 px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-semibold transition-colors">Cancel</button>
                    <button id="submitManualRegister" class="flex-1 px-4 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-semibold shadow-lg shadow-primary-200 transition-all flex items-center justify-center">
                        <i class="fas fa-save mr-2"></i>Register Device
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Manual Register Submit
        document.getElementById('submitManualRegister').addEventListener('click', function() {
            const name = document.getElementById('manual_name').value;
            const deviceId = document.getElementById('manual_device_id').value;
            const fcmToken = document.getElementById('manual_fcm_token').value;

            if (!deviceId || !fcmToken) {
                showNotification('Device ID and FCM Token are required', 'error');
                return;
            }

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

            fetch('/api/devices/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    device_id: deviceId,
                    fcm_token: fcmToken,
                    name: name || 'Manual Device',
                    metadata: { type: 'manual_entry', entry_time: new Date().toISOString() }
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Device registered successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('Failed: ' + (data.message || 'Unknown error'), 'error');
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-save mr-2"></i>Register Device';
                }
            })
            .catch(error => {
                showNotification('Error: ' + error, 'error');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-save mr-2"></i>Register Device';
            });
        });
        // Platform Toggle Logic
        document.querySelectorAll('.platform-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Reset all
                document.querySelectorAll('.platform-btn').forEach(b => {
                    b.classList.remove('border-primary-500', 'bg-primary-50');
                });
                // Set active
                this.classList.add('border-primary-500', 'bg-primary-50');
                
                const platform = this.dataset.platform;
                
                if (platform === 'spotify') {
                    document.getElementById('spotify-input-group').classList.remove('hidden');
                    document.getElementById('youtube-input-group').classList.add('hidden');
                } else {
                    document.getElementById('spotify-input-group').classList.add('hidden');
                    document.getElementById('youtube-input-group').classList.remove('hidden');
                }
            });
        });

        // Quick command buttons
        document.querySelectorAll('.quick-command').forEach(button => {
            button.addEventListener('click', function() {
                const action = this.dataset.action;
                const platform = this.dataset.platform || 'spotify';
                const uri = this.dataset.uri || '';

                // Add visual feedback
                const originalBg = this.style.background;
                this.style.background = 'linear-gradient(to right, #3b82f6, #06b6d4)';
                this.style.color = 'white';

                fetch('/api/commands/send-to-all', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        action: action,
                        platform: platform,
                        media_url: uri
                    })
                })
                .then(response => response.json())
                .then(data => {
                    // Show success message
                    showNotification('Command sent successfully!', 'success');

                    // Reset button after delay
                    setTimeout(() => {
                        this.style.background = originalBg;
                        this.style.color = '';

                        // Reload after short delay to update statuses
                        setTimeout(() => location.reload(), 1000);
                    }, 1000);
                })
                .catch(error => {
                    showNotification('Error sending command: ' + error, 'error');
                    // Reset button immediately on error
                    this.style.background = originalBg;
                    this.style.color = '';
                });
            });
        });

        // Send to single device
        document.querySelectorAll('.send-single').forEach(button => {
            button.addEventListener('click', function() {
                const deviceId = this.dataset.deviceId;
                const uri = prompt('Enter Spotify URI:', 'spotify:track:4cOdK2wGLETKBW3PvgPWqT');

                if (uri) {
                    // Add visual feedback
                    const originalColor = this.style.color;
                    const originalBg = this.style.background;
                    this.style.background = 'linear-gradient(to right, #3b82f6, #06b6d4)';
                    this.style.color = 'white';

                    fetch(`/api/commands/send-to-device/${deviceId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            action: 'play',
                            platform: 'spotify', // Default for now
                            media_url: uri
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        showNotification('Command sent to device!', 'success');

                        // Reset button
                        setTimeout(() => {
                            this.style.background = originalBg;
                            this.style.color = originalColor;
                            setTimeout(() => location.reload(), 1000);
                        }, 1000);
                    })
                    .catch(error => {
                        showNotification('Error: ' + error, 'error');
                        this.style.background = originalBg;
                        this.style.color = originalColor;
                    });
                }
            });
        });

        // Remove device
        document.querySelectorAll('.remove-device').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Are you sure you want to remove this device from the dashboard?')) {
                    const deviceId = this.dataset.deviceId;

                    fetch(`/api/devices/${deviceId}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        showNotification(data.message || 'Device removed!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    })
                    .catch(error => {
                        showNotification('Error: ' + error, 'error');
                    });
                }
            });
        });

        // Notification function
        function showNotification(message, type) {
            // Remove existing notification
            const existing = document.querySelector('.notification-toast');
            if (existing) existing.remove();

            const colors = {
                success: 'bg-gradient-to-r from-green-500 to-emerald-500',
                error: 'bg-gradient-to-r from-red-500 to-rose-500',
                info: 'bg-gradient-to-r from-blue-500 to-cyan-500'
            };

            const icon = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };

            const toast = document.createElement('div');
            toast.className = `notification-toast fixed top-6 right-6 ${colors[type]} text-white px-6 py-4 rounded-xl shadow-2xl z-[100] flex items-center smooth-transition`;
            toast.style.transform = 'translateX(100%)';
            toast.innerHTML = `
                <i class="fas ${icon[type]} mr-3 text-xl"></i>
                <div>
                    <p class="font-semibold">${type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Info'}</p>
                    <p class="text-sm opacity-90">${message}</p>
                </div>
            `;

            document.body.appendChild(toast);

            // Animate in
            setTimeout(() => {
                toast.style.transform = 'translateX(0)';
            }, 10);

            // Auto remove after 5 seconds
            setTimeout(() => {
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }, 5000);

            // Click to dismiss
            toast.addEventListener('click', () => {
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            });
        }

        // ── Log Level Filter ────────────────────────────────────────────
        document.querySelectorAll('.log-filter').forEach(btn => {
            btn.addEventListener('click', function() {
                const filterLevel = this.dataset.level;

                // Toggle active state on buttons
                document.querySelectorAll('.log-filter').forEach(b => b.style.opacity = '0.5');
                this.style.opacity = '1';

                document.querySelectorAll('.log-row').forEach(row => {
                    const rowLevel = row.dataset.level;
                    if (filterLevel === 'all') {
                        row.style.display = '';
                    } else if (filterLevel === 'error') {
                        // Show both error and critical
                        row.style.display = (rowLevel === 'error' || rowLevel === 'critical') ? '' : 'none';
                    } else {
                        row.style.display = (rowLevel === filterLevel) ? '' : 'none';
                    }

                    // Also hide/show the stack trace row immediately following
                    const nextRow = row.nextElementSibling;
                    if (nextRow && !nextRow.classList.contains('log-row')) {
                        nextRow.style.display = row.style.display;
                        if (row.style.display === 'none') nextRow.classList.add('hidden');
                    }
                });
            });
        });
    });
    </script>
</body>
</html>
