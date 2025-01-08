<?php

namespace App\Models\ProcessTracker;

use App\Models\BaseMongoModel;

class TrackerProcessIteration extends BaseMongoModel
{
    protected $guarded = [];
    protected $casts = [];

    public function trackerProcess()
    {
        return $this->belongsTo(TrackerProcess::class);
    }
}
