<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmbeddedProductPlacement extends Model
{
    use HasFactory;

    protected $fillable = ['embedded_product_id', 'quote_type_id', 'position'];

    public function quoteType()
    {
        return $this->belongsTo(QuoteType::class, 'quote_type_id', 'id');
    }
}
