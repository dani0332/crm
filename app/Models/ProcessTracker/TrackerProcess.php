<?php

namespace App\Models\ProcessTracker;

use App\Enums\ProcessTracker\ProcessTrackerTypeEnum;
use App\Models\BaseMongoModel;

class TrackerProcess extends BaseMongoModel
{
    protected $fillable = [
        'type',
        'iterations',
    ];
    protected $casts = [
        'type' => ProcessTrackerTypeEnum::class,
    ];

    public function tracker()
    {
        return $this->belongsTo(Tracker::class);
    }
}
