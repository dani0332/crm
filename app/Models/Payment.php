<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['code', 'payment_status_id', 'plan_id', 'captured_amount', 'captured_at', 'payment_methods_code', 'insurance_provider_id', 'created_by', 'updated_by', 'is_approved', 'reference', 'collection_type'];
    protected $forceDeleting = true;


    public function paymentable()
    {
        return $this->morphTo();
    }

    public function plan()
    {
        return $this->belongsTo('App\Models\Plan', 'plan_id');
    }

    public function paymentStatus()
    {
        return $this->belongsTo('App\Models\PaymentStatus', 'payment_status_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo('App\Models\PaymentMethod', 'payment_methods_code', 'code');
    }
}
