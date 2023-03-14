<?php

namespace App\Repositories;

use App\Models\CustomerAdditionalContact;

class AdditionalContactRepository extends BaseRepository
{
    public function model()
    {
        return CustomerAdditionalContact::class;
    }
}
