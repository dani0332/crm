<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['code', 'payment_status_id', 'plan_id', 'captured_amount', 'captured_at', 'authorized_at', 'payment_methods_code', 'insurance_provider_id', 'created_by', 'updated_by', 'is_approved', 'reference', 'collection_type', 'payment_link'];
    protected $forceDeleting = true;

    public function paymentable()
    {
        return $this->morphTo();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function personalPlan()
    {
        return $this->belongsTo(PersonalPlan::class, 'plan_id');
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

    public function paymentStatusLogs()
    {
        return $this->hasMany('App\Models\PaymentStatusLog', 'payment_code', 'code');
    }

    public function getCreatedAtAttribute($date)
    {
        return (! empty($date)) ? Carbon::createFromFormat('Y-m-d H:i:s', $date)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : '';
    }

    public function getAuthorizedAtAttribute($date)
    {
        return (! empty($date)) ? Carbon::createFromFormat('Y-m-d H:i:s', $date)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : '';
    }

    public function getCapturedAtAttribute($date)
    {
        return (! empty($date)) ? Carbon::createFromFormat('Y-m-d H:i:s', $date)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : '';
    }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function healthPlan()
    {
        return $this->belongsTo('App\Models\HealthPlan', 'plan_id');
    }

    public function paymentStatusLog()
    {
        return $this->hasOne('App\Models\PaymentStatusLog', 'payment_code', 'code')->latest();
    }
}
