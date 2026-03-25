<?php

namespace App\Models;

use App\Traits\GenericQueriesAllLobs;
use App\Traits\TransformsAuditables;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerMembers extends Model
{
    use GenericQueriesAllLobs, HasFactory, TransformsAuditables;

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

            if (isset($transformedNew['is_policy_holder']) && $transformedNew['is_policy_holder'] == 'true') {
                $audit->event = 'Member Added (Policy Holder)';
            } elseif (isset($transformedNew['is_principal']) && $transformedNew['is_principal'] == 'true') {
                $audit->event = 'Member Added (Principal)';
            } else {
                $audit->event = 'Member Added';
            }
        } else {

            if (! isset($transformedOld['firstName']) || ! isset($transformedOld['lastName'])) {
                $transformedOld['name'] = $data['model']?->first_name.' '.$data['model']?->last_name;
                $transformedNew['name'] = $data['model']?->first_name.' '.$data['model']?->last_name;
            }

            if (! empty($transformedNew['deletedAt'])) {
                $audit->event = 'Member Removed';
            } elseif (isset($transformedOld['isPolicyHolder']) && isset($transformedNew['isPolicyHolder'])
                && $transformedOld['isPolicyHolder'] == 'true' && $transformedNew['isPolicyHolder'] == 'false'
            ) {
                $audit->event = 'Policy Holder Removed';
            } elseif (isset($transformedOld['isPrincipal']) && $transformedOld['isPrincipal'] == 'true'
                && isset($transformedNew['isPrincipal']) && $transformedNew['isPrincipal'] == 'false'
            ) {
                $audit->event = 'Principal Removed';
            } elseif (
                isset($transformedOld['isPolicyHolder']) && $transformedOld['isPolicyHolder'] == 'false'
                && isset($transformedNew['isPolicyHolder']) && $transformedNew['isPolicyHolder'] == 'true'
            ) {
                $audit->event = 'Policy Holder Added';
            } elseif (
                isset($transformedOld['isPrincipal']) && $transformedOld['isPrincipal'] == 'false'
                && isset($transformedNew['isPrincipal']) && $transformedNew['isPrincipal'] == 'true'
            ) {
                $audit->event = 'Principal Added';
            } else {
                $audit->event = 'Member Updated';
                if ($data['model']?->is_policy_holder == 1) {
                    $audit->event .= ' (Policy Holder)';
                } elseif ($data['model']?->is_principal == 1) {
                    $audit->event .= ' (Principal)';
                }
            }
        }

        return $data;
    }
}
