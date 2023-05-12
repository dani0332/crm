<?php

namespace App\Repositories;

use App\Models\ClaimHistory;

class ClaimHistoryRepository extends BaseRepository
{
    public function model()
    {
        return ClaimHistory::class;
    }
}
