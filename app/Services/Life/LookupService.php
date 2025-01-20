<?php

namespace App\Services\Life;

use App\Models\Lookup;
use App\Enums\LookupsEnum;
use App\Services\BaseService;
use App\Services\SendUpdateLogService;

class LookupService extends BaseService
{
    public function getBy($column, $value)
    {
        return Lookup::where($column, $value)->get();
    }

    public function getSendUpdateOptions($quoteTypeId)
    {
        return Lookup::where([
            'code' => LookupsEnum::SEND_UPDATE_CODE,
            'parent_id' => null,
        ])
            ->withChildTree($quoteTypeId, app(SendUpdateLogService::class)->checkSendUpdatePermissions())
            ->get();
    }
}
