<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Ken extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'KenService';
    }
}
