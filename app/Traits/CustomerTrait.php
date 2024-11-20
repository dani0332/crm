<?php

namespace App\Traits;

use App\Enums\QuoteTypes;
use Illuminate\Http\File;

trait CustomerTrait
{
    public function fetchFormattedAddress($address)
    {
        $addressOrder = [
            'villa_apartment_office_no',
            'floor_no',
            'villa_building_name',
            'street_name',
            'area',
            'city',
            'landmark',
        ];

        return implode(', ', array_filter(array_map(
            fn($key) => $address[$key] ?? null,
            $addressOrder
        )));
    }
}
