<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RuleType extends Model
{
    use HasFactory;

    public const LEAD_SOURCE = 'lead source';
    public const CAR_MAKE_MODEL = 'car make and model';
    public const VEHICLE_USE = 'vehicle use';

    /**
     * const @var array
     */
    const RULE_TYPES_LIST = [
        self::LEAD_SOURCE,
        self::CAR_MAKE_MODEL,
        self::VEHICLE_USE,
    ];
}
