<?php

namespace App\Services\Life;

use App\Enums\CacheKeyEnum;
use App\Models\Nationality;
use App\Services\BaseService;
use App\Services\Cache\CacheManager;

class NationalityService extends BaseService
{
    public function getActive()
    {
        return Nationality::withActive()->select('id', 'text')->get();
    }

    public function getGCCNationalityIds(): array
    {
        return CacheManager::remember(CacheKeyEnum::NATIONALITIES_GCC_IDS, function () {
            return Nationality::whereIn('text', ['Saudi Arabian', 'Saudi', 'Kuwaiti', 'Omani', 'Qatari', 'Bahraini'])->pluck('id')->toArray();
        });
    }

    public function getUAENationalityIds(): array
    {
        return CacheManager::remember(CacheKeyEnum::NATIONALITIES_UAE_IDS, function () {
            return Nationality::whereIn('text', ['Emirati', 'Emarat'])->pluck('id')->toArray();
        });
    }
}
