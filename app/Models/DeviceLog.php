<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceLog extends Model
{
    protected $fillable = [
        'device_id',
        'level',
        'event',
        'message',
        'stack_trace',
        'context',
        'device_timestamp',
    ];

    protected $casts = [
        'context'          => 'array',
        'device_timestamp' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────

    public function device()
    {
        return $this->belongsTo(Device::class, 'device_id', 'device_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────

    public function scopeErrors($query)
    {
        return $query->whereIn('level', ['error', 'critical']);
    }

    public function scopeForDevice($query, string $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
