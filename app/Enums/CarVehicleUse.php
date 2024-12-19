<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class CarVehicleUse extends Enum
{
    public const PRIVATE = 'private';
    public const COMMERCIAL = 'commercial';

    const VEHICLE_USE_LIST = [
        self::PRIVATE,
        self::COMMERCIAL,
    ];
}
