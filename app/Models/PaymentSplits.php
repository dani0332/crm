<?php

namespace App\Models;

use App\Enums\PaymentStatusEnum;
use App\Enums\SageEnum;
use Config;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class PaymentSplits extends Model implements Auditable
{
    use AuditableTrait, HasFactory;

    protected $auditEvents = [
        'updated',
    ];
    protected $table = 'payment_splits';
    protected $fillable = [
        'code', 'sr_no', 'payment_method', 'check_detail', 'payment_amount', 'due_date', 'payment_status_id', 'collection_amount', 'bank_reference_number', 'decline_reason_id', 'insurer_payment_link',
        'decline_custom_reason', 'sage_reciept_id', 'digital_wallet', 'payment_link', 'payment_link_created_at', 'payment_allocation_status',
        'captured_at', 'authorized_at', 'is_approved', 'reference',  'discount_value', 'verified_by', 'verified_at', 'price_vat_applicable', 'price_vat', 'commission_vat_applicable', 'commission_vat',
        'insurer_receipt_number',
    ];
    protected $appends = [
        'prepayment_receipt_status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'prepayment_receipt_status' => 'array',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'code', 'code');
    }

    public function paymentStatus()
    {
        return $this->belongsTo(PaymentStatus::class, 'payment_status_id', 'id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method', 'code');
    }

    public function documents()
    {
        return $this->hasMany(QuoteDocument::class, 'payment_split_id', 'id');
    }

    public function transformAudit(array $data): array
    {
        $data['old_values']['code'] = strtolower($this->code);

        return $data;
    }

    public function sageApiLogs()
    {
        return $this->morphMany(SageApiLog::class, 'section');
    }

    public function verifiedByUser()
    {
        return $this->belongsTo(User::class, 'verified_by', 'id');
    }

    // render payment status PAID if payment status is CAPTURED on BA Request
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

    public function getVerifiedAtAttribute($value)
    {
        if (! empty($value)) {
            $date_time_format = Config::get('constants.DATETIME_DISPLAY_FORMAT');

            return $this->asDateTime($value)->timezone(config('app.timezone'))->format($date_time_format);
        }

        return null;
    }

    public function processJob()
    {
        return $this->hasOne(CcPaymentProcess::class)->failed();
    }

    public function paymentCharges(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PaymentCharge::class, 'payment_split_id', 'id');
    }

    public function sageProcess()
    {
        return $this->morphOne(SageProcess::class, 'model');
    }

    /**
     * Get the prepayment receipt status
     */
    protected function prepaymentReceiptStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                $prepaymentData = [];
                $sageProcess = $this->sageProcess;
                $sageApiLogs = $this->sageApiLogs->keyBy('step')->toArray();
                $prepaymentCreationResponse = isset($sageApiLogs[2]) ? json_decode($sageApiLogs[2]['response'], true) : null;
                $batchNumber = $prepaymentCreationResponse['BatchNumber'] ?? null;
                $lastStep = end($sageApiLogs);
                $isPrepaymentAlreadyPosted = $lastStep && $lastStep['sage_request_type'] == SageEnum::SRT_POST_PP_REC && $lastStep['status'] == SageEnum::STATUS_SUCCESS;
                $sageProcessFailed = $sageProcess?->status == SageEnum::SAGE_PROCESS_FAILED_STATUS;
                $prepaymentData['lastStep'] = $lastStep;
                $prepaymentData['sageProcess'] = [$sageProcess?->id, $sageProcess?->status];
                $prepaymentData['isPrepaymentAlreadyPosted'] = $isPrepaymentAlreadyPosted;
                $prepaymentData['showPrepaymentPostButton'] = $batchNumber && ! $isPrepaymentAlreadyPosted && (! $sageProcess || $sageProcessFailed);
                $prepaymentData['batchNumber'] = $batchNumber;

                return $prepaymentData;
            }
        );
    }
}
