<?php

namespace App\Http\Traits;

use App\Models\VehicleType;

trait VehicleTypeTrait
{
    public function getVehicleTypes()
    {
        // Fetch all the active vehicle types.
        return VehicleType::where('is_active', '=', 1)->whereRaw('text = category')->orderBy('created_at', 'desc')->get();
    }
}
