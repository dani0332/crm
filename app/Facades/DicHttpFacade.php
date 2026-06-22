<?php

namespace App\Facades;

use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicHttpClient;
use Illuminate\Support\Facades\Facade;

class DicHttpFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return DicHttpClient::class;
    }
}
