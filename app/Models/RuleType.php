<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RuleType extends Model
{
    use HasFactory;

    public const LEAD_SOURCE        =   'lead source';
    public const MOTOR_CORPORATE    =   'motor corporate';

    /**
     * const @var array
     */
    const RULE_TYPES_LIST = [
        self::LEAD_SOURCE,
        self::MOTOR_CORPORATE
    ];
}
