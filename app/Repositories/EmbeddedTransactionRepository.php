<?php

namespace App\Repositories;

use App\Models\EmbeddedTransaction;

class EmbeddedTransactionRepository extends BaseRepository
{
    public function model()
    {
        return EmbeddedTransaction::class;
    }

    public function fetchByQuoteTypeId($quoteTypeId, $id)
    {
        return $this->with('product.embeddedProduct', 'paymentStatus')
        ->where('quote_type_id', $quoteTypeId)
        ->where('quote_request_id', $id)
        ->get();
    }
}
