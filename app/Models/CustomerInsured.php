<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerInsured extends Model
{
    protected $table = 'customer_insured';
    protected $guarded = [];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function insured()
    {
        return $this->belongsTo(Insured::class);
    }
}
