<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Marshall extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'MarshallService';
    }
}
