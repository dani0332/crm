<?php

namespace App\Repositories;

use App\Models\PolicyIssuanceStatus;

class PolicyIssuanceStatusRepository extends BaseRepository
{
    public function model()
    {
        return PolicyIssuanceStatus::class;
    }

    public function fetchGetColumns(array $columns = ['*'])
    {
        return $this->get($columns);
    }
}
