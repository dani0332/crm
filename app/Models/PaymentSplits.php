<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class PaymentSplits extends Model implements Auditable
{
    use HasFactory,AuditableTrait;

    protected $auditEvents = [
        'updated',
    ];

    protected $table = 'payment_splits';
    protected $fillable = ['code', 'sr_no', 'payment_method', 'check_detail', 'payment_amount', 'due_date', 'payment_status_id', 'collection_amount', 'bank_reference_number', 'decline_reason_id',
        'decline_custom_reason', 'sage_reciept_id', 'digital_wallet', 'payment_link', 'payment_link_created_at', 'payment_allocation_status',
        'captured_at', 'authorized_at', 'is_approved', 'reference',
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
}
