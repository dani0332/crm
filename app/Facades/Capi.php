<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Capi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'CapiService';
    }
}
