<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignTrack extends Model
{
    protected $fillable = ['campaign_id', 'media_url', 'media_title', 'position_order', 'duration_seconds'];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
