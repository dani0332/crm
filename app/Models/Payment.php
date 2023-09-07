<?php

namespace App\Models;

use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\RolesEnum;
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

    /**
     * @return bool
     */
    public function getAllowAttribute()
    {
        return $this->attributes['allow'] = ($this->payment_status_id != PaymentStatusEnum::CAPTURED && $this->payment_status_id != PaymentStatusEnum::AUTHORISED && ! auth()->user()->hasRole(RolesEnum::PA));
    }

    /**
     * @return bool
     */
    public function getCopyLinkButtonAttribute()
    {
        return $this->attributes['copy_link_button'] = ($this->allow && optional($this->paymentMethod)->code == PaymentMethodsEnum::CreditCard && $this->payment_status_id != PaymentStatusEnum::PAID);
    }

    /**
     * @return bool
     */
    public function getApproveButtonAttribute()
    {
        return $this->attributes['approve_button'] = (optional($this->paymentMethod)->code != PaymentMethodsEnum::CreditCard && $this->payment_status_id != PaymentStatusEnum::PAID && $this->payment_status_id != PaymentStatusEnum::CAPTURED
            && ! auth()->user()->hasRole(RolesEnum::PA));
    }

    /**
     * @return bool
     */
    public function getApprovedButtonAttribute()
    {
        return $this->attributes['approved_button'] = ($this->payment_status_id == PaymentStatusEnum::PAID);
    }

    /**
     * @return bool
     */
    public function getEditButtonAttribute()
    {
        return $this->attributes['edit_button'] = ($this->allow && $this->payment_status_id != PaymentStatusEnum::PAID);
    }

    public function scopeWithPermissions($q)
    {
        $this->attributes['allow_approve'] = 1;

        return $q;
    }

    public function getCapturedAmountAttribute($value)
    {
        return number_format($value, 2, '.', '');
    }

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
        return $this->belongsTo(PaymentStatus::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_methods_code', 'code');
    }

    public function paymentStatusLogs()
    {
        return $this->hasMany(PaymentStatusLog::class, 'payment_code', 'code');
    }

    public function getCreatedAtAttribute($date)
    {
        return (! empty($date)) ? Carbon::parse($date)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : '';
    }

    public function getAuthorizedAtAttribute($date)
    {
        return (! empty($date)) ? Carbon::parse($date)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : '';
    }

    public function getCapturedAtAttribute($date)
    {
        return (! empty($date)) ? Carbon::parse($date)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : '';
    }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function healthPlan()
    {
        return $this->belongsTo(HealthPlan::class, 'plan_id');
    }

    // Should be removed because it's already declared above paymentStatusLogs()
    public function paymentStatusLog()
    {
        return $this->hasMany(PaymentStatusLog::class, 'payment_code', 'code')->latest();
    }

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

}
