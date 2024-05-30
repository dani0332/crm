<?php

namespace App\Enums;

use BenSampo\Enum\Enum;
use ReflectionClass;

final class QuoteSyncStatus extends Enum
{
    const WAITING = 0;
    const INPROGRESS = 1;
    const FAILED = 2;
    const COMPLETED = 3;

    public static function getName($id)
    {
        $types = [
            0 => 'Waiting',
            1 => 'In Progress',
            2 => 'Failed',
            3 => 'Completed',
        ];

        return $types[$id];
    }

    public static function getOptions()
    {
        $oClass = new ReflectionClass(__CLASS__);
        $constants = $oClass->getConstants();
        $retval = [];
        foreach ($constants as $name => $val) {
            $retval[$val] = $name;
        }

        return $retval;
    }
}
