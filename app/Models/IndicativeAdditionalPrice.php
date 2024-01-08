<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicativeAdditionalPrice extends Model
{
    use HasFactory;

    protected $table = 'indicative_additional_prices';

    protected $fillable = [
        'price_vat_applicable',
        'price_vat_not_applicable',
        'total_price',
        'send_update_log_id',
    ];
}
