<?php

namespace App\Repositories;

class TierRepository extends BaseRepository
{

    public function model()
    {
        return \App\Models\Tier::class;
    }

    public function fetchGetData()
    {
        return $this->orderBy('created_at', 'desc')->simplePaginate(10)->withQueryString();
    }
}