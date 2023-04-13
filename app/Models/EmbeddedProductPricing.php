<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmbeddedProductPricing extends Model
{
    use HasFactory;

    protected $fillable = ['embedded_product_id', 'price'];
}
