<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'type',
        'quote_type_id',
        'quote_uuid',
        'office_number',
        'floor_number',
        'building_name',
        'street',
        'area',
        'city',
        'landmark',
        'is_default',
    ];
}
