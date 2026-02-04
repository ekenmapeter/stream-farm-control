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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
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
                                        <i class="fas fa-play-circle mr-2 text-primary-500"></i>Action Type
                                    </label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <label class="flex items-center p-4 border rounded-xl cursor-pointer smooth-transition hover:border-primary-300 hover:bg-primary-50">
                                            <input type="radio" name="action" value="play" class="h-5 w-5 text-primary-600" checked>
                                            <div class="ml-3">
                                                <p class="font-medium text-gray-900">Play</p>
                                                <p class="text-xs text-gray-500">Start playback</p>
                                            </div>
                                        </label>
                                        <label class="flex items-center p-4 border rounded-xl cursor-pointer smooth-transition hover:border-primary-300 hover:bg-primary-50">
                                            <input type="radio" name="action" value="pause" class="h-5 w-5 text-primary-600">
                                            <div class="ml-3">
                                                <p class="font-medium text-gray-900">Pause</p>
                                                <p class="text-xs text-gray-500">Pause playback</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <label for="spotify_uri" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fab fa-spotify mr-2 text-green-500"></i>Spotify URI
                                    </label>
                                    <div class="relative">
                                        <input type="text" id="spotify_uri" name="spotify_uri" value="spotify:track:4cOdK2wGLETKBW3PvgPWqT" class="w-full p-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 smooth-transition">
                                        <button type="button" onclick="document.getElementById('spotify_uri').value='spotify:playlist:37i9dQZF1DXcBWIGoYBM5M'" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded-lg smooth-transition">
                                            Playlist
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2">Format: spotify:track:xxx or spotify:playlist:xxx</p>
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
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <button class="quick-command p-4 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl text-center smooth-transition hover:shadow-md hover:border-green-300" data-action="play" data-uri="spotify:track:4cOdK2wGLETKBW3PvgPWqT">
                            <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-play text-green-600"></i>
                            </div>
                            <p class="font-medium text-green-800">Play Track</p>
                            <p class="text-xs text-green-600 mt-1">Sample Song</p>
                        </button>
                        <button class="quick-command p-4 bg-gradient-to-r from-blue-50 to-cyan-50 border border-blue-200 rounded-xl text-center smooth-transition hover:shadow-md hover:border-blue-300" data-action="play" data-uri="spotify:playlist:37i9dQZF1DXcBWIGoYBM5M">
                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-list-music text-blue-600"></i>
                            </div>
                            <p class="font-medium text-blue-800">Play Playlist</p>
                            <p class="text-xs text-blue-600 mt-1">Today's Top Hits</p>
                        </button>
                        <button class="quick-command p-4 bg-gradient-to-r from-amber-50 to-yellow-50 border border-amber-200 rounded-xl text-center smooth-transition hover:shadow-md hover:border-amber-300" data-action="pause">
                            <div class="h-10 w-10 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-pause text-amber-600"></i>
                            </div>
                            <p class="font-medium text-amber-800">Pause All</p>
                            <p class="text-xs text-amber-600 mt-1">Stop playback</p>
                        </button>
                        <button class="quick-command p-4 bg-gradient-to-r from-red-50 to-rose-50 border border-red-200 rounded-xl text-center smooth-transition hover:shadow-md hover:border-red-300" data-action="stop">
                            <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-stop text-red-600"></i>
                            </div>
                            <p class="font-medium text-red-800">Stop All</p>
                            <p class="text-xs text-red-600 mt-1">End all streams</p>
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
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Quick command buttons
        document.querySelectorAll('.quick-command').forEach(button => {
            button.addEventListener('click', function() {
                const action = this.dataset.action;
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
                        spotify_uri: uri
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
                            spotify_uri: uri
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
    });
    </script>
</body>
</html>
