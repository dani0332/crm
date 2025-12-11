<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicHttpClient;

class AdnicHttpFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return AdnicHttpClient::class;
    }
}