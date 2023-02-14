<?php

namespace App\Repositories;

use App\Models\InsuranceProvider;
use App\Models\Nationality;

class InsuranceProviderRepository extends BaseRepository
{
    public function model() {
        return InsuranceProvider::class;
    }

    public function fetchGetList()
    {
        return $this->withActive()->orderBy('sort_order')->get();
    }
}
