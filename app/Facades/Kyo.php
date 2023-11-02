<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Kyo extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'KyoService';
    }
}
