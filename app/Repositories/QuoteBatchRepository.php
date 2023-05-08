<?php

namespace App\Repositories;

use App\Models\QuoteBatches;

class QuoteBatchRepository extends BaseRepository
{
    public function model()
    {
        return QuoteBatches::class;
    }
}
