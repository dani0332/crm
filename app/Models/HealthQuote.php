<?php

namespace App\Models;

use App\Enums\QuoteTypeId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class HealthQuote extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'health_quote_request';
    protected $guarded = [];

    public function emirate()
    {
        return $this->belongsTo(Emirate::class, 'emirate_of_your_visa_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function currentlyInsured()
    {
        return $this->hasOne(InsuranceProvider::class, 'id', 'currently_insured_id');
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nationality_id');
    }

    public function paymentStatus()
    {
        return $this->belongsTo(PaymentStatus::class, 'payment_status_id');
    }

    public function quoteStatus()
    {
        return $this->belongsTo(QuoteStatus::class, 'quote_status_id');
    }

    public function healthCoverFor()
    {
        return $this->belongsTo(HealthCoverFor::class, 'cover_for_id');
    }

    public function maritalStatus()
    {
        return $this->belongsTo(MartialStatus::class, 'marital_status_id');
    }

    public function healthQuoteRequestDetail()
    {
        return $this->hasOne(HealthQuoteRequestDetail::class, 'id', 'health_quote_request_id');
    }

    public function currentProvider()
    {
        return $this->hasOne(InsuranceProvider::class, 'id', 'currently_insured_with_id');
    }

    public function memberDetails()
    {
        return $this->hasMany(HealthMemberDetail::class, 'id', 'primary_member_id');
    }

    public function memberCategory()
    {
        return $this->belongsTo(MemberCategory::class, 'member_category_id', 'id');
    }

    public function salaryBand()
    {
        return $this->belongsTo(SalaryBand::class, 'salary_band_id', 'id');
    }

    public function advisor()
    {
        return $this->hasOne(User::class, 'id', 'advisor_id');
    }

    public function wcAdvisor()
    {
        return $this->hasOne(User::class, 'id', 'wcu_id');
    }

    public function getFullNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function documents()
    {
        return $this->morphMany(QuoteDocument::class, 'quote_documentable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function members()
    {
        return $this->morphMany(CustomerMembers::class, 'quote');
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'paymentable');
    }

    public function plan()
    {
        return $this->belongsTo(HealthPlan::class, 'plan_id');
    }

    public function lostReason()
    {
        return $this->belongsTo(LostReason::class, 'lost_reason_id');
    }

    public function healthLeadType()
    {
        return $this->belongsTo(HealthLeadType::class, 'lead_type_id');
    }

    public function quoteRequestEntityMapping()
    {
        return $this->hasOne(QuoteRequestEntityMapping::class, 'quote_request_id')
            ->where('quote_type_id', QuoteTypeId::Health);
    }

    public function customerMembers()
    {
        return $this->morphMany(CustomerMembers::class, 'quote');
    }
}
