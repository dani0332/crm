<?php

namespace App\Facades;

use App\Services\ProcessTracker\ProcessTrackerService;
use Illuminate\Support\Facades\Facade;

class ProcessTracker extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ProcessTrackerService::class;
    }
}
