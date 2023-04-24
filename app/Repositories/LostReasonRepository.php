<?php

namespace App\Repositories;

use App\Models\LostReasons;

class LostReasonRepository extends BaseRepository
{
    public function model()
    {
        return LostReasons::class;
    }
}
