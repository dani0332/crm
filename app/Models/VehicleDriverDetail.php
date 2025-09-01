<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VehicleDriverDetail extends Model
{
    protected $table = 'vehicle_driver_details';

    public function quoteable(): MorphTo
    {
        return $this->morphTo();
    }
}
