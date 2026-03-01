<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const headers = {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN': csrfToken};

    // ── Tab Switching ────────────────────────────────────────────────────
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
            this.classList.add('active');
            document.getElementById('tab-' + this.dataset.tab).classList.remove('hidden');
        });
    });

    // ── Assign Form: Platform Toggle ─────────────────────────────────────
    document.querySelectorAll('.assign-platform-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.assign-platform-btn').forEach(b => {
                b.classList.remove('border-primary-500','bg-primary-50');
                b.classList.add('border-gray-200');
            });
            this.classList.add('border-primary-500','bg-primary-50');
            this.classList.remove('border-gray-200');
            const p = this.dataset.platform;
            document.getElementById('assign-spotify-group').classList.toggle('hidden', p !== 'spotify');
            document.getElementById('assign-youtube-group').classList.toggle('hidden', p !== 'youtube');
        });
    });

    // ── Assign Form: Device selection count & submit button ──────────────
    function updateSelectionCount() {
        const checked = document.querySelectorAll('.device-checkbox:checked').length;
        document.getElementById('selectedCount').textContent = checked;
        document.getElementById('assignSubmitBtn').disabled = checked === 0;
        // Toggle check icon visibility
        document.querySelectorAll('.device-checkbox').forEach(cb => {
            const icon = cb.closest('label').querySelector('.fa-check');
            if (icon) icon.parentElement.classList.toggle('bg-primary-500', cb.checked);
            if (icon) icon.parentElement.classList.toggle('border-primary-500', cb.checked);
            if (icon) icon.classList.toggle('hidden', !cb.checked);
        });
    }
    document.querySelectorAll('.device-checkbox').forEach(cb => cb.addEventListener('change', updateSelectionCount));

    document.getElementById('selectAllDevices')?.addEventListener('click', function() {
        document.querySelectorAll('.device-checkbox:not(:disabled)').forEach(cb => cb.checked = true);
        updateSelectionCount();
    });
    document.getElementById('deselectAllDevices')?.addEventListener('click', function() {
        document.querySelectorAll('.device-checkbox').forEach(cb => cb.checked = false);
        updateSelectionCount();
    });

    // ── Assign Form: Set media_url before submit ─────────────────────────
    document.getElementById('assignForm')?.addEventListener('submit', function(e) {
        const platform = document.querySelector('.assign-platform-btn input:checked')?.value || 'spotify';
        const url = platform === 'spotify'
            ? document.getElementById('assign_media_spotify').value
            : document.getElementById('assign_media_youtube').value;
        if (!url) { e.preventDefault(); showNotification('Please enter a media URL/URI', 'error'); return; }
        document.getElementById('assign_media_url').value = url;
    });

    // ── Broadcast: Platform Toggle ───────────────────────────────────────
    document.querySelectorAll('.bcast-platform-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.bcast-platform-btn').forEach(b => {
                b.classList.remove('border-primary-500','bg-primary-50');
                b.classList.add('border-gray-200');
            });
            this.classList.add('border-primary-500','bg-primary-50');
            this.classList.remove('border-gray-200');
            const p = this.dataset.platform;
            document.getElementById('bcast-spotify-group').classList.toggle('hidden', p !== 'spotify');
            document.getElementById('bcast-youtube-group').classList.toggle('hidden', p !== 'youtube');
        });
    });

    // ── Modal: Platform Toggle ───────────────────────────────────────────
    document.querySelectorAll('.modal-platform-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.modal-platform-btn').forEach(b => {
                b.classList.remove('border-primary-500','bg-primary-50');
                b.classList.add('border-gray-200');
            });
            this.classList.add('border-primary-500','bg-primary-50');
            this.classList.remove('border-gray-200');
            const p = this.dataset.platform;
            document.getElementById('modal-spotify-group').classList.toggle('hidden', p !== 'spotify');
            document.getElementById('modal-youtube-group').classList.toggle('hidden', p !== 'youtube');
            this.querySelector('input').checked = true;
        });
    });

    // ── Single Device: Open Modal ────────────────────────────────────────
    document.querySelectorAll('.send-single').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('modal_device_id').value = this.dataset.deviceId;
            document.getElementById('playDeviceModal').classList.remove('hidden');
        });
    });

    // ── Single Device: Confirm Assignment ────────────────────────────────
    document.getElementById('confirmPlayDevice')?.addEventListener('click', function() {
        const deviceId = document.getElementById('modal_device_id').value;
        const platform = document.querySelector('input[name="modal_platform"]:checked').value;
        const uri = platform === 'spotify'
            ? document.getElementById('modal_spotify_uri').value
            : document.getElementById('modal_youtube_url').value;
        const title = document.getElementById('modal_media_title')?.value || '';

        if (!uri) { showNotification('Please enter a URL/URI', 'error'); return; }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Assigning...';

        fetch('/api/assignments', {
            method: 'POST', headers: headers,
            body: JSON.stringify({ device_ids: [parseInt(deviceId)], platform: platform, media_url: uri, media_title: title || null })
        })
        .then(r => r.json())
        .then(data => {
            showNotification(data.success ? 'Task assigned to device!' : ('Failed: ' + (data.message || 'Unknown')), data.success ? 'success' : 'error');
            document.getElementById('playDeviceModal').classList.add('hidden');
            this.disabled = false; this.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Assign & Play';
            if (data.success) setTimeout(() => location.reload(), 1000);
        })
        .catch(err => {
            showNotification('Error: ' + err, 'error');
            this.disabled = false; this.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Assign & Play';
        });
    });

    // ── Assignment Controls (Play/Pause/Stop) ────────────────────────────
    document.querySelectorAll('.assignment-control').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const action = this.dataset.action;
            const endpoint = action === 'stop' ? `/api/assignments/${id}` : `/api/assignments/${id}/control`;
            const method = action === 'stop' ? 'DELETE' : 'POST';
            const body = action === 'stop' ? undefined : JSON.stringify({ action: action });

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch(endpoint, { method: method, headers: headers, body: body })
            .then(r => r.json())
            .then(data => {
                showNotification(data.message || 'Done', data.success ? 'success' : 'error');
                setTimeout(() => location.reload(), 800);
            })
            .catch(err => {
                showNotification('Error: ' + err, 'error');
                this.disabled = false; this.innerHTML = action;
            });
        });
    });

    // ── Manual Register ──────────────────────────────────────────────────
    document.getElementById('submitManualRegister')?.addEventListener('click', function() {
        const name = document.getElementById('manual_name').value;
        const deviceId = document.getElementById('manual_device_id').value;
        const fcmToken = document.getElementById('manual_fcm_token').value;
        if (!deviceId || !fcmToken) { showNotification('Device ID and FCM Token are required', 'error'); return; }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

        fetch('/api/devices/register', {
            method: 'POST', headers: headers,
            body: JSON.stringify({ device_id: deviceId, fcm_token: fcmToken, name: name || 'Manual Device', metadata: { type: 'manual_entry' } })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { showNotification('Device registered!', 'success'); setTimeout(() => location.reload(), 1000); }
            else { showNotification('Failed: ' + (data.message || 'Error'), 'error'); this.disabled = false; this.innerHTML = '<i class="fas fa-save mr-2"></i>Register Device'; }
        })
        .catch(err => { showNotification('Error: ' + err, 'error'); this.disabled = false; this.innerHTML = '<i class="fas fa-save mr-2"></i>Register Device'; });
    });

    // ── Remove Device ────────────────────────────────────────────────────
    document.querySelectorAll('.remove-device').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Remove this device from the dashboard?')) return;
            fetch(`/api/devices/${this.dataset.deviceId}`, { method: 'DELETE', headers: headers })
            .then(r => r.json())
            .then(data => { showNotification(data.message || 'Removed', 'success'); setTimeout(() => location.reload(), 800); })
            .catch(err => showNotification('Error: ' + err, 'error'));
        });
    });

    // ── Log Level Filter ─────────────────────────────────────────────────
    document.querySelectorAll('.log-filter').forEach(btn => {
        btn.addEventListener('click', function() {
            const lvl = this.dataset.level;
            document.querySelectorAll('.log-filter').forEach(b => b.style.opacity = '0.5');
            this.style.opacity = '1';
            document.querySelectorAll('.log-row').forEach(row => {
                const rl = row.dataset.level;
                row.style.display = (lvl === 'all') ? '' : (lvl === 'error' ? ((rl === 'error' || rl === 'critical') ? '' : 'none') : (rl === lvl ? '' : 'none'));
                const next = row.nextElementSibling;
                if (next && !next.classList.contains('log-row')) { next.style.display = row.style.display; if (row.style.display === 'none') next.classList.add('hidden'); }
            });
        });
    });

    // ── Toast Notification ───────────────────────────────────────────────
    function showNotification(message, type) {
        const existing = document.querySelector('.notification-toast');
        if (existing) existing.remove();
        const colors = { success: 'bg-gradient-to-r from-green-500 to-emerald-500', error: 'bg-gradient-to-r from-red-500 to-rose-500', info: 'bg-gradient-to-r from-blue-500 to-cyan-500' };
        const icons = { success: 'fa-check-circle', error: 'fa-exclamation-triangle', info: 'fa-info-circle' };
        const toast = document.createElement('div');
        toast.className = `notification-toast fixed top-6 right-6 ${colors[type]} text-white px-6 py-4 rounded-xl shadow-2xl z-[200] flex items-center smooth-transition slide-in`;
        toast.innerHTML = `<i class="fas ${icons[type]} mr-3 text-xl"></i><div><p class="font-semibold">${type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Info'}</p><p class="text-sm opacity-90">${message}</p></div>`;
        document.body.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 5000);
        toast.addEventListener('click', () => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); });
    }
});
</script>
