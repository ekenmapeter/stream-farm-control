<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'device_id',
        'fcm_token',
        'status',
        'last_seen',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_seen' => 'datetime'
    ];
}
