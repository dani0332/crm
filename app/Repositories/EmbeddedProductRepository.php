<?php

namespace App\Repositories;

use App\Models\EmbeddedProduct;

class EmbeddedProductRepository extends BaseRepository
{
    public function model()
    {
        return EmbeddedProduct::class;
    }
}
