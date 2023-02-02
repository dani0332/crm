<?php

namespace App\Repositories;

use App\Models\InsuranceProvider;
use App\Models\Nationality;

class InsuranceProviderRepository extends BaseRepository
{
    public function model() {
        return InsuranceProvider::class;
    }
}
