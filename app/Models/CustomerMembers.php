<?php

namespace App\Models;

use App\Enums\CustomerTypeEnum;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\TransformsAuditables;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CustomerMembers extends Model implements AuditableContract
{
    use Auditable, GenericQueriesAllLobs, HasFactory, TransformsAuditables;

    protected $guarded = ['id'];
    protected $appends = [
        'name',
    ];
    protected array $auditRelationMap = [
        'nationality_id' => ['relation' => 'nationality', 'field' => 'text'],
        'member_category_id' => ['relation' => 'memberCategory', 'field' => 'text'],
        'salary_band_id' => ['relation' => 'salaryBand', 'field' => 'text'],
        'emirate_of_your_visa_id' => ['relation' => 'emirate', 'field' => 'text'],
        'marital_status_id' => ['relation' => 'maritalStatus', 'field' => 'text'],
        'visa_category_id' => ['relation' => 'visaCategory', 'field' => 'text'],
    ];

    public function getNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nationality_id');
    }

    public function relation()
    {
        return $this->belongsTo(Lookup::class, 'relation_code', 'code');
    }

    public function emirate()
    {
        return $this->hasOne(Emirate::class, 'id', 'emirate_of_your_visa_id');
    }

    public function memberCategory()
    {
        return $this->belongsTo(MemberCategory::class, 'member_category_id', 'id');
    }

    public function salaryBand()
    {
        return $this->belongsTo(SalaryBand::class, 'salary_band_id', 'id');
    }

    public function quote()
    {
        return $this->morphTo();
    }

    public function getDobAttribute($value)
    {
        return ! empty($value) ? Carbon::parse($value)->format(config('constants.DATE_FORMAT_ONLY')) : $value;
    }

    public function scopeIndividual($query)
    {
        return $query->where('customer_type', CustomerTypeEnum::Individual);
    }

    public function scopeNotThirdPartyPayer($query)
    {
        return $query->where('is_third_party_payer', false);
    }

    public function scopePolicyHolder($query)
    {
        return $query->where('is_policy_holder', true);
    }

    /**
     * @return mixed
     */
    public function scopeByQuoteType($query, $quoteType)
    {
        $quoteModelObject = $this->getModelObject(strtolower($quoteType));

        return $query->whereHasMorph('quote', $quoteModelObject);
    }

    public function getAgeAttribute()
    {
        $dob = Carbon::parse($this->dob);

        return $dob->age;
    }

    public function maritalStatus()
    {
        return $this->belongsTo(MartialStatus::class, 'marital_status_id');
    }

    public function visaCategory()
    {
        return $this->belongsTo(VisaCategory::class, 'visa_category_id', 'id');
    }

    public static function customizeAuditTransformation($data): array
    {
        $audit = &$data['audit'];
        $transformedOld = &$data['transformedOld'];
        $transformedNew = &$data['transformedNew'];

        if ($audit->event == 'created') {
            $audit->event = self::resolveCreatedEvent($transformedNew);
        } else {
            self::populateNameIfMissing($data, $transformedOld, $transformedNew);
            $audit->event = self::resolveUpdatedEvent($transformedOld, $transformedNew, $data['model']);
        }

        return $data;
    }

    private static function resolveCreatedEvent(array $transformedNew): string
    {
        if (($transformedNew['is_policy_holder'] ?? null) == 'true') {
            $isInsured = ($transformedNew['is_insured'] ?? null) == 'true';

            return $isInsured ? 'member_added (Policyholder)' : 'member_added (Non-insured Policyholder)';
        }

        if (($transformedNew['is_principal'] ?? null) == 'true') {
            return 'member_added (Principal)';
        }

        return 'member_added';
    }

    private static function populateNameIfMissing(array $data, array &$transformedOld, array &$transformedNew): void
    {
        if (isset($transformedOld['first_name']) && isset($transformedOld['last_name'])) {
            return;
        }

        $fullName = $data['model']?->first_name.' '.$data['model']?->last_name;
        $transformedOld['name'] = $fullName;
        $transformedNew['name'] = $fullName;
    }

    private static function resolveUpdatedEvent(array $transformedOld, array $transformedNew, $model): string
    {
        $oldPolicyHolder = $transformedOld['is_policy_holder'] ?? null;
        $newPolicyHolder = $transformedNew['is_policy_holder'] ?? null;
        $oldPrincipal = $transformedOld['is_principal'] ?? null;
        $newPrincipal = $transformedNew['is_principal'] ?? null;

        $event = match (true) {
            ! empty($transformedNew['deleted_at']) => 'member_deleted',
            $oldPolicyHolder === 'true' && $newPolicyHolder === 'false' => 'member_updated (Policyholder Removed)',
            $oldPolicyHolder === 'false' && $newPolicyHolder === 'true' => 'member_updated (Policyholder Added)',
            $oldPrincipal === 'true' && $newPrincipal === 'false' => 'member_updated (Principal Removed)',
            $oldPrincipal === 'false' && $newPrincipal === 'true' => 'member_updated (Principal Added)',
            $model?->is_policy_holder == 1 => 'member_updated (Policyholder)',
            $model?->is_principal == 1 => 'member_updated (Principal)',
            default => 'member_updated',
        };

        return $event;
    }
}
