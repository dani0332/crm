<?php

namespace App\Facades;

use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicHttpClient;
use Illuminate\Support\Facades\Facade;

class AdnicHttpFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return AdnicHttpClient::class;
    }
}
