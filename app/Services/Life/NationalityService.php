<?php

namespace App\Services\Life;

use App\Models\Nationality;
use App\Services\BaseService;

class NationalityService extends BaseService
{
    public function getActive()
    {
        return Nationality::withActive()->select('id', 'text')->get();
    }

    public function getGCCNationalityIds(): array
    {
        return Nationality::whereIn('text', ['Saudi Arabian', 'Saudi', 'Kuwaiti', 'Omani', 'Qatari', 'Bahraini'])->pluck('id')->toArray();
    }

    public function getUAENationalityIds(): array
    {
        return Nationality::whereIn('text', ['Emirati', 'Emarat'])->pluck('id')->toArray();
    }
}
