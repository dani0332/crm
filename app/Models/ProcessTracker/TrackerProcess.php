<?php

namespace App\Models\ProcessTracker;

use App\Enums\ProcessTracker\ProcessTrackerTypeEnum;
use App\Models\BaseMongoModel;

class TrackerProcess extends BaseMongoModel
{
    protected $fillable = [
        'type',
    ];
    protected $casts = [
        'type' => ProcessTrackerTypeEnum::class,
    ];

    public function tracker()
    {
        return $this->belongsTo(Tracker::class);
    }

    public function iterations()
    {
        return $this->hasMany(TrackerProcessIteration::class);
    }
}
