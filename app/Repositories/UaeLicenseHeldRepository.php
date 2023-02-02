<?php

namespace App\Repositories;

use App\Models\Nationality;
use App\Models\UAELicenseHeldFor;

class UaeLicenseHeldRepository extends BaseRepository
{
    public function model() {
        return UAELicenseHeldFor::class;
    }
}
