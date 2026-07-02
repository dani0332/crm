<?php

namespace App\Support;

final class HealthPlanVersionHelper
{
    public static function nextMinorVersion(float $version): float
    {
        return round($version + 0.1, 1);
    }

    public static function nextMajorVersion(float $version): float
    {
        return (float) ceil($version);
    }
}
