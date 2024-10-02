<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessTracker extends Model
{
    protected $fillable = ['uuid', 'process_type', 'tracker_data'];

    protected $casts = [
        'tracker_data' => 'array',
    ];
}