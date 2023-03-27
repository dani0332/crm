<?php

namespace App\Repositories;

use App\Models\PersonalQuoteType;

class PersonalQuoteTypeRepository extends BaseRepository
{
    public function model()
    {
        return PersonalQuoteType::class;
    }

    /**
     * get by code
     *
     * @return mixed
     */
    public function fetchGetByCode($code)
    {
        return $this->where('code', $code)->first();
    }
}
