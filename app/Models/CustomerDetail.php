<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CustomerDetail extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'customer_details';
    protected $guarded = [];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id', 'customer_id');
    }
}
