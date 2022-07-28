<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAdditionalInfo extends Model
{
    use HasFactory;

    protected $table = 'customer_additional_info';

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id', 'customer_id');
    }
}
