<?php

namespace App\Models;

use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\RolesEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Payment extends Model implements Auditable
{
    use AuditableTrait;

    protected $auditEvents = [
        'updated',
    ];
    protected $table = 'payments';
    protected $keyType = 'string';
    protected $fillable = [
        'code', 'payment_status_id', 'plan_id', 'captured_amount',
        'captured_at', 'authorized_at', 'payment_methods_code', 'insurance_provider_id', 'created_by',
        'updated_by', 'is_approved', 'reference', 'collection_type', 'payment_link', 'total_payments', 'credit_approval', 'frequency', 'discount_type', 'discount_reason', 'custom_reason', 'notes', 'total_price', 'collection_date', 'payer_name', 'paid_by',
        'discount_value', 'total_amount', 'payment_allocation_status', 'decline_reason_id', 'decline_custom_reason', 'discount_custom_reason',
        'commission_vat', 'commission_without_vat', 'commission_vat_applicable', 'commission_vat_not_applicable', 'commission', 'tax_invoice_number', 'broker_invoice_number', 'insurer_invoice_date', 'invoice_description', 'insurer_tax_number', 'transaction_payment_status', 'insurer_commmission_invoice_number', 'commmission_percentage',
        'send_update_log_id', 'policy_expiry_date', 'paymentable_id', 'paymentable_type', 'price_vat_applicable', 'price_vat',

    ];
    protected $forceDeleting = true;

    public function transformAudit(array $data): array
    {
        $data['old_values']['code'] = strtolower($this->code);

        return $data;
    }

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

    public function travelPlan()
    {
        return $this->belongsTo(TravelPlan::class, 'plan_id');
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

    public function getCustomerPaymentInstrument()
    {
        return $this->belongsTo(
            CustomerPaymentInstrument::class,
            'customer_payment_instrument_id',
            'id'
        );
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

    public function carPlan()
    {
        return $this->belongsTo(CarPlan::class, 'plan_id');
    }

    public function bikePlan()
    {
        return $this->belongsTo(CarPlan::class, 'plan_id');
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

    public function paymentSplits()
    {
        return $this->hasMany(PaymentSplits::class, 'code', 'code');
    }

    public function policyIssuer()
    {
        return $this->belongsTo(User::class, 'policy_issuer_id', 'id')->select(['id', 'name', 'email']);
    }

    // render payment status PAID if payment status is CAPTURED
    public function getPaymentStatusIdAttribute($value)
    {
        if ($value == PaymentStatusEnum::CAPTURED) {
            return PaymentStatusEnum::PAID;
        } elseif ($value == PaymentStatusEnum::PARTIAL_CAPTURED) {
            return PaymentStatusEnum::PARTIALLY_PAID;
        } else {
            return $value;
        }
    }

    public function isPaymentAuthorized()
    {
        return $this->payment_status_id == PaymentStatusEnum::AUTHORISED;
    }

    public function isPaid()
    {
        return $this->payment_status_id == PaymentStatusEnum::PAID;
    }

    public function sendUpdateLog()
    {
        return $this->belongsTo(SendUpdateLog::class, 'send_update_log_id', 'id');
    }

    public function scopeMainLeadPayment($q)
    {
        return $q->whereNull('send_update_log_id');
    }
}
