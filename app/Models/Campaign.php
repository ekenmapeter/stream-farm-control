<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = ['name', 'platform', 'is_active'];

    public function tracks()
    {
        return $this->hasMany(CampaignTrack::class)->orderBy('position_order');
    }

    public function assignments()
    {
        return $this->hasMany(DeviceAssignment::class);
    }
}
