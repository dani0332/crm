<?php

namespace App\Repositories;

use App\Models\EmbeddedTransaction;

class EmbeddedTransactionRepository extends BaseRepository
{
    public function model()
    {
        return EmbeddedTransaction::class;
    }
}
