<?php

namespace App\Traits;

use App\Models\CustomerMembers;
use Carbon\Carbon;

trait HealthServiceUtils
{
    /**
     * Normalise a member array or model into the camelCase shape expected by CAPI/Ken payloads.
     *
     * @param  CustomerMembers|array<string, mixed>  $member
     */
    public function prepareMemberDetailPayload(CustomerMembers|array $member): array
    {
        $id = $member['id'] ?? 'temp-';
        $dob = ! empty($member['dob']) ? Carbon::parse($member['dob'])->toDateString() : null;

        return [
            'id' => str_starts_with((string) ($id), 'temp-') ? null : $id,
            'firstName' => $member['first_name'] ?? null,
            'lastName' => $member['last_name'] ?? null,
            'dob' => $dob,
            'gender' => $member['gender'] ?? null,
            'nationalityId' => $member['nationality_id'] ?? null,
            'emirateOfYourVisaId' => $member['emirate_of_your_visa_id'] ?? null,
            'salaryBandId' => $member['salary_band_id'] ?? null,
            'memberCategoryId' => $member['member_category_id'] ?? null,
            'visaCategoryId' => $member['visa_category_id'] ?? null,
            'relationCode' => $member['relation_code'] ?? null,
            'maritalStatusId' => $member['marital_status_id'] ?? null,
            'isInsured' => ($member['is_insured'] ?? null) == 1,
            'isPolicyHolder' => ($member['is_policy_holder'] ?? null) == 1,
            'isPrincipal' => ($member['is_principal'] ?? null) == 1,
            'isPecMarked' => ($member['pec'] ?? $member['is_pec_marked'] ?? null) == 1,
        ];
    }
}
