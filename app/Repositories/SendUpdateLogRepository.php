<?php

namespace App\Repositories;

use App\Models\SendUpdateLog;

class SendUpdateLogRepository extends BaseRepository
{
    public function model()
    {
        return SendUpdateLog::class;
    }
}
