<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmbeddedProductOption extends Model
{
    use HasFactory;

    protected $fillable = ['embedded_product_id', 'price', 'variant', 'is_active'];

    public function embeddedProduct()
    {
        return $this->belongsTo(EmbeddedProduct::class, 'embedded_product_id', 'id');
    }

    public function transactions()
    {
        return $this->hasMany(EmbeddedTransaction::class, 'product_id', 'id');
    }
}
