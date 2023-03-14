<?php

namespace App\Repositories;

use App\Models\CustomerAdditionalContact;
use App\Models\User;

class AdditionalContactRepository extends BaseRepository
{
    public function model()
    {
        return CustomerAdditionalContact::class;
    }
}
