<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class PostMark extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'PostMark';
    }
}
