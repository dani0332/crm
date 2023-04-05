<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmbeddedProduct extends Model
{
    public function embeddedproductable()
    {
        return $this->morphTo();
    }
}
