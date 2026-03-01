<!-- Manual Register Modal -->
<div id="manualRegisterModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[100] flex items-center justify-center hidden">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="bg-slate-800 px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-bold text-white"><i class="fas fa-plus-circle mr-2 text-primary-400"></i>Manual Device Registration</h3>
            <button onclick="document.getElementById('manualRegisterModal').classList.add('hidden')" class="text-slate-400 hover:text-white transition-colors"><i class="fas fa-times"></i></button>
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

<!-- Play on Single Device Modal -->
<div id="playDeviceModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[100] flex items-center justify-center hidden">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="bg-gradient-to-r from-primary-600 to-blue-500 px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-bold text-white"><i class="fas fa-play-circle mr-2"></i>Assign to Device</h3>
            <button onclick="document.getElementById('playDeviceModal').classList.add('hidden')" class="text-white/80 hover:text-white transition-colors"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6">
            <input type="hidden" id="modal_device_id">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Platform</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="modal-platform-btn flex items-center p-3 border-2 rounded-xl cursor-pointer smooth-transition border-primary-500 bg-primary-50" data-platform="spotify">
                            <input type="radio" name="modal_platform" value="spotify" class="hidden" checked>
                            <i class="fab fa-spotify text-green-500 mr-2"></i><span class="font-medium">Spotify</span>
                        </label>
                        <label class="modal-platform-btn flex items-center p-3 border-2 rounded-xl cursor-pointer smooth-transition border-gray-200" data-platform="youtube">
                            <input type="radio" name="modal_platform" value="youtube" class="hidden">
                            <i class="fab fa-youtube text-red-500 mr-2"></i><span class="font-medium">YouTube</span>
                        </label>
                    </div>
                </div>
                <div id="modal-spotify-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Spotify URI</label>
                    <input type="text" id="modal_spotify_uri" value="spotify:track:4cOdK2wGLETKBW3PvgPWqT" class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                </div>
                <div id="modal-youtube-group" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">YouTube URL</label>
                    <input type="text" id="modal_youtube_url" placeholder="https://www.youtube.com/watch?v=..." class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Task Label <span class="text-gray-400">(optional)</span></label>
                    <input type="text" id="modal_media_title" placeholder="e.g. Morning Vibes" class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                </div>
            </div>
            <div class="mt-8 flex space-x-3">
                <button onclick="document.getElementById('playDeviceModal').classList.add('hidden')" class="flex-1 px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-semibold transition-colors">Cancel</button>
                <button id="confirmPlayDevice" class="flex-1 px-4 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-semibold shadow-lg shadow-primary-200 transition-all flex items-center justify-center">
                    <i class="fas fa-paper-plane mr-2"></i>Assign & Play
                </button>
            </div>
        </div>
    </div>
</div>
