<?php

namespace App\Traits;

use App\Services\Logger\LoggerService;

trait Logable
{
    public function scopeLogRawSql($query, ?string $title = null)
    {
        $query->tap(function ($q) use ($title) {
            LoggerService::sql($title ?? self::class, $q);
        });
    }
}
