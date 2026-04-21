<?php

namespace App\Services\HealthRevamp;

use App\Enums\EmirateEnum;
use App\Enums\HealthCoverForEnum;
use App\Enums\HealthInsureEnum;
use App\Enums\HealthPolicyHolderEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\MemberCategoryEnum;
use App\Enums\QuoteTypeId;
use App\Enums\SalaryBandEnum;
use App\Enums\VisaCategoryEnum;
use App\Models\CustomerInsured;
use App\Models\CustomerMembers;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;
use App\Services\Life\NationalityService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class HealthQuoteRevampMigrationMutator
{
    public function __construct(
        private readonly HealthQuoteRevampMigrationQueries $queries,
        private readonly HealthQuoteRevampMigrationContext $context,
    ) {}

    public function applyAll(HealthQuote $hqr): void
    {
        $this->fillMissingPrincipals($hqr);
        $this->updatePolicyHoldersFromNameMatch($hqr);
        $this->insertIndividualPolicyHolderWhenNoNameMatch($hqr);
        $this->insertMembersWhenNoneAndNoActiveInsured($hqr);
        $this->insertMembersWhenNoneAndInsuredIndividual($hqr);
        DB::transaction(function () use ($hqr) {
            $this->applyCoverForIdUpdates($hqr);
            $this->applyInsureAndPolicyHolderCodes($hqr);
            $this->applyMaritalStatusUpdates($hqr);
            $this->normalizeGenderValues($hqr);
            $this->applyPolicyHolderCategoryCode($hqr);
            $this->applyHealthQuoteSalaryBandAndVisaFromMemberCategory($hqr);
            $this->applyMemberRelationSalaryAndVisa($hqr);
            $this->applyHealthQuoteMemberCategoryRemap($hqr);
            $this->applyCustomerMemberCategoryRemap($hqr);
        });
    }

    private function fillMissingPrincipals(HealthQuote $hqr): void
    {
        $members = $this->queries->healthMembersBaseQuery($hqr)
            ->where('customer_type', 'Individual')
            ->where('is_third_party_payer', false)
            ->orderBy('id')
            ->get();

        if ($members->isEmpty() || $members->contains(fn (CustomerMembers $m) => $m->is_principal)) {
            return;
        }

        $nameMatch = $members->first(function (CustomerMembers $m) use ($hqr) {
            return $m->first_name === $hqr->first_name && $m->last_name === $hqr->last_name;
        });

        $toUpdate = $nameMatch ?? $members->first();
        if ($toUpdate) {
            $toUpdate->is_principal = true;
            $toUpdate->save();
        }
    }

    private function updatePolicyHoldersFromNameMatch(HealthQuote $hqr): void
    {
        $members = $this->queries->healthMembersBaseQuery($hqr)
            ->where('customer_type', 'Individual')
            ->where('is_third_party_payer', false)
            ->get();

        if ($members->isEmpty() || $members->contains(fn (CustomerMembers $m) => $m->is_policy_holder)) {
            return;
        }

        $nameMatches = $members->filter(function (CustomerMembers $m) use ($hqr) {
            return $m->first_name === $hqr->first_name
                && $m->last_name === $hqr->last_name
                && $this->context->memberIsAtLeastYearsOld($m->dob, 18);
        })->sortBy([
            ['is_principal', 'desc'],
            ['id', 'asc'],
        ]);

        $chosen = $nameMatches->first();
        if ($chosen) {
            $chosen->is_policy_holder = true;
            $chosen->save();
        }
    }

    private function insertIndividualPolicyHolderWhenNoNameMatch(HealthQuote $hqr): void
    {
        if (! $this->shouldInsertIndividualPolicyHolderWhenNoNameMatch($hqr)) {
            return;
        }

        $code = 'IND-'.$hqr->customer_id.'-'.$this->queries->nextIndividualCodeSuffix($hqr->customer_id);

        CustomerMembers::query()->create([
            'quote_type' => HealthQuoteRevampMigrationQueries::QUOTE_MORPH,
            'quote_id' => $hqr->id,
            'customer_entity_id' => $hqr->customer_id,
            'code' => $code,
            'customer_type' => 'Individual',
            'first_name' => $hqr->first_name,
            'last_name' => $hqr->last_name,
            'salary_band_id' => $hqr->salary_band_id,
            'is_pec_marked' => false,
            'visa_category_id' => $hqr->visa_category_id,
            'dob' => $hqr->dob ? Carbon::parse($hqr->dob)->format('Y-m-d') : null,
            'nationality_id' => $hqr->nationality_id,
            'is_insured' => false,
            'is_policy_holder' => true,
            'is_principal' => false,
            'is_third_party_payer' => false,
        ]);
    }

    private function shouldInsertIndividualPolicyHolderWhenNoNameMatch(HealthQuote $hqr): bool
    {
        if ($hqr->customer_id === null) {
            return false;
        }

        $hasMembers = $this->queries->healthMembersBaseQuery($hqr)
            ->where('customer_type', 'Individual')
            ->where('is_third_party_payer', false)
            ->exists();

        $hasAdultNameMatchPolicyHolder = $this->queries->healthMembersBaseQuery($hqr)
            ->where('customer_type', 'Individual')
            ->where('is_third_party_payer', false)
            ->where('is_policy_holder', true)
            ->where('first_name', $hqr->first_name)
            ->where('last_name', $hqr->last_name)
            ->whereNotNull('dob')
            ->get()
            ->contains(fn (CustomerMembers $m) => $this->context->memberIsAtLeastYearsOld($m->dob, 18));

        return $hasMembers && ! $hasAdultNameMatchPolicyHolder;
    }

    private function insertMembersWhenNoneAndNoActiveInsured(HealthQuote $hqr): void
    {
        if ($hqr->customer_id === null) {
            return;
        }

        $hasMembers = $this->queries->healthMembersBaseQuery($hqr)
            ->where('is_third_party_payer', false)
            ->exists();

        if ($hasMembers) {
            return;
        }

        $hasActiveInsured = CustomerInsured::query()
            ->where('quote_request_id', $hqr->id)
            ->where('quote_type_id', QuoteTypeId::Health)
            ->where('customer_id', $hqr->customer_id)
            ->where('is_active', true)
            ->exists();

        if ($hasActiveInsured) {
            return;
        }

        $code = 'IND-'.$hqr->customer_id.'-'.$this->queries->nextIndividualCodeSuffix($hqr->customer_id);

        CustomerMembers::query()->create([
            'quote_type' => HealthQuoteRevampMigrationQueries::QUOTE_MORPH,
            'quote_id' => $hqr->id,
            'customer_entity_id' => $hqr->customer_id,
            'code' => $code,
            'customer_type' => 'Individual',
            'first_name' => $hqr->first_name,
            'last_name' => $hqr->last_name,
            'salary_band_id' => $hqr->salary_band_id,
            'is_pec_marked' => false,
            'visa_category_id' => $hqr->visa_category_id,
            'dob' => $hqr->dob ? Carbon::parse($hqr->dob)->format('Y-m-d') : null,
            'nationality_id' => $hqr->nationality_id,
            'is_insured' => true,
            'is_policy_holder' => true,
            'is_principal' => true,
            'is_third_party_payer' => false,
        ]);
    }

    private function insertMembersWhenNoneAndInsuredIndividual(HealthQuote $hqr): void
    {
        if ($hqr->customer_id === null) {
            return;
        }

        $hasMembers = $this->queries->healthMembersBaseQuery($hqr)
            ->where('is_third_party_payer', false)
            ->exists();

        if ($hasMembers) {
            return;
        }

        $row = CustomerInsured::query()
            ->where('quote_request_id', $hqr->id)
            ->where('quote_type_id', QuoteTypeId::Health)
            ->where('customer_id', $hqr->customer_id)
            ->where('is_active', true)
            ->join('insured', 'insured.id', '=', 'customer_insured.insured_id')
            ->where('insured.customer_type', 'Individual')
            ->select('customer_insured.*')
            ->first();

        if (! $row) {
            return;
        }

        $code = 'IND-'.$hqr->customer_id.'-'.$this->queries->nextIndividualCodeSuffix($hqr->customer_id);

        CustomerMembers::query()->create([
            'quote_type' => HealthQuoteRevampMigrationQueries::QUOTE_MORPH,
            'quote_id' => $hqr->id,
            'customer_entity_id' => $hqr->customer_id,
            'code' => $code,
            'customer_type' => 'Individual',
            'first_name' => $hqr->first_name,
            'last_name' => $hqr->last_name,
            'salary_band_id' => $hqr->salary_band_id,
            'is_pec_marked' => false,
            'visa_category_id' => $hqr->visa_category_id,
            'dob' => $hqr->dob ? Carbon::parse($hqr->dob)->format('Y-m-d') : null,
            'nationality_id' => $hqr->nationality_id,
            'is_insured' => true,
            'is_policy_holder' => true,
            'is_principal' => true,
            'is_third_party_payer' => false,
        ]);
    }

    private function applyCoverForIdUpdates(HealthQuote $hqr): void
    {
        if (in_array((int) $hqr->cover_for_id, [HealthCoverForEnum::INDIVIDUAL->value, HealthCoverForEnum::FAMILY->value], true)) {
            $hqr->cover_for_id = HealthCoverForEnum::INDIVIDUAL_AND_FAMILIES->value;
        }

        if ((int) $hqr->member_category_id === MemberCategoryEnum::DOMESTIC_WORKER->value) {
            $hqr->cover_for_id = HealthCoverForEnum::DOMESTIC_HELPER->value;
        }

        $hqr->save();
    }

    private function applyInsureAndPolicyHolderCodes(HealthQuote $hqr): void
    {
        $allowedTypes = $this->context->allowedCustomerTypesForQuote($hqr);

        $members = $this->queries->healthMembersBaseQuery($hqr)
            ->where('is_third_party_payer', false)
            ->whereIn('customer_type', $allowedTypes)
            ->get();

        if ($members->isEmpty()) {
            return;
        }

        $insuredCnt = $members->filter(fn (CustomerMembers $m) => $m->is_insured)->count();
        $policyHolder = $members->first(fn (CustomerMembers $m) => $m->is_policy_holder);
        $phIsInsured = $policyHolder ? (bool) $policyHolder->is_insured : false;

        $hqr->insure_code = $insuredCnt > 1 ? HealthInsureEnum::MYSELF_AND_MY_FAMILY_MEMBERS->value : HealthInsureEnum::ONLY_MYSELF->value;
        $hqr->policy_holder_code = $phIsInsured ? HealthPolicyHolderEnum::ME->value : HealthPolicyHolderEnum::OTHER_ADULT_FAMILY_MEMBER->value;
        $hqr->save();
    }

    private function applyMaritalStatusUpdates(HealthQuote $hqr): void
    {
        $g = $hqr->gender;
        $msId = $hqr->marital_status_id;

        if ($msId === null && in_array($g, ['M', 'F', 'FS', 'Female', 'Male'], true)) {
            $hqr->marital_status_id = MaritalStatusEnum::SINGLE->value;
        } elseif ($msId === null && $g === 'FM') {
            $hqr->marital_status_id = MaritalStatusEnum::MARRIED->value;
        } elseif ($msId !== null && (int) $msId === MaritalStatusEnum::UNMARRIED_PARTNER->value) {
            $hqr->marital_status_id = MaritalStatusEnum::SINGLE->value;
        }

        $hqr->save();

        $this->queries->healthMembersBaseQuery($hqr)
            ->where('customer_type', 'Individual')
            ->where('is_third_party_payer', false)
            ->get()
            ->each(function (CustomerMembers $cm) use ($hqr) {
                $g = $cm->gender;
                if ($cm->is_principal) {
                    $cm->marital_status_id = $hqr->marital_status_id;
                } elseif (! $cm->is_principal && in_array($g, ['M', 'F', 'FS', 'Female', 'Male'], true)) {
                    $cm->marital_status_id = MaritalStatusEnum::SINGLE->value;
                } elseif (! $cm->is_principal && $g === 'FM') {
                    $cm->marital_status_id = MaritalStatusEnum::MARRIED->value;
                }
                $cm->save();
            });
    }

    private function normalizeGenderValues(HealthQuote $hqr): void
    {
        $g = $hqr->gender;
        if (in_array($g, ['M', 'Male'], true)) {
            $hqr->gender = 'M';
        } elseif (in_array($g, ['F', 'FS', 'Female', 'FM'], true)) {
            $hqr->gender = 'F';
        }
        $hqr->save();

        PersonalQuote::query()
            ->where('quote_id', $hqr->id)
            ->where('quote_type_id', QuoteTypeId::Health)
            ->whereIn('gender', ['M', 'Male'])
            ->update(['gender' => 'M']);

        PersonalQuote::query()
            ->where('quote_id', $hqr->id)
            ->where('quote_type_id', QuoteTypeId::Health)
            ->whereIn('gender', ['F', 'FS', 'Female', 'FM'])
            ->update(['gender' => 'F']);

        $this->queries->healthMembersBaseQuery($hqr)
            ->where('customer_type', 'Individual')
            ->where('is_third_party_payer', false)
            ->whereIn('gender', ['M', 'Male'])
            ->update(['gender' => 'M']);

        $this->queries->healthMembersBaseQuery($hqr)
            ->where('customer_type', 'Individual')
            ->where('is_third_party_payer', false)
            ->whereIn('gender', ['F', 'FS', 'Female', 'FM'])
            ->update(['gender' => 'F']);
    }

    private function applyPolicyHolderCategoryCode(HealthQuote $hqr): void
    {
        $gccNationalityIds = app(NationalityService::class)->getGCCNationalityIds();
        $uaeNationalityIds = app(NationalityService::class)->getUAENationalityIds();
        $nid = (int) $hqr->nationality_id;
        if (in_array($nid, $gccNationalityIds, true)) {
            $hqr->policy_holder_category_code = 'GCC_CITIZEN';
        } elseif (in_array($nid, $uaeNationalityIds, true)) {
            $hqr->policy_holder_category_code = 'UAE_CITIZEN';
        } else {
            $hqr->policy_holder_category_code = 'RESIDENT';
        }

        $hqr->save();
    }

    private function applyHealthQuoteSalaryBandAndVisaFromMemberCategory(HealthQuote $hqr): void
    {
        $mc = (int) $hqr->member_category_id;

        $salaryBand = match ($mc) {
            MemberCategoryEnum::DOMESTIC_WORKER->value => SalaryBandEnum::BELOW_OR_EQ_4000->value,
            MemberCategoryEnum::EMPLOYEE_2->value => SalaryBandEnum::BETWEEN_4001_AND_12000->value,
            MemberCategoryEnum::EMPLOYEE_1->value => SalaryBandEnum::BELOW_OR_EQ_4000->value,

            MemberCategoryEnum::SELF_EMPLOYED_FREELANCE->value,
            MemberCategoryEnum::INVESTOR_PARTNER->value,
            MemberCategoryEnum::GOLDEN_VISA->value => SalaryBandEnum::ABOVE_12000->value,

            MemberCategoryEnum::DEPENDENT_SIBLING_OR_OTHER_RELATIVES->value,
            MemberCategoryEnum::DEPENDENT_PARENT->value,
            MemberCategoryEnum::DEPENDENT_CHILD->value,
            MemberCategoryEnum::DEPENDENT_SPOUSE->value => SalaryBandEnum::NO_SALARY_DEPENDENTS_OR_CHILDREN->value,

            default => $hqr->salary_band_id,
        };

        $months = $this->context->monthsSinceDob($this->context->dobToDateString($hqr->dob));
        $visa = match (true) {
            in_array($mc, [MemberCategoryEnum::DOMESTIC_WORKER->value, MemberCategoryEnum::EMPLOYEE_2->value, MemberCategoryEnum::EMPLOYEE_1->value, MemberCategoryEnum::DEPENDENT_SIBLING_OR_OTHER_RELATIVES->value, MemberCategoryEnum::DEPENDENT_PARENT->value, MemberCategoryEnum::DEPENDENT_SPOUSE->value], true) => VisaCategoryEnum::SPONSORED_EMPLOYER_FAMILY->value,
            $mc === MemberCategoryEnum::SELF_EMPLOYED_FREELANCE->value => VisaCategoryEnum::SELF_EMPLOYED_FREELANCE->value,
            $mc === MemberCategoryEnum::INVESTOR_PARTNER->value => VisaCategoryEnum::INVESTOR_PARTNER->value,
            $mc === MemberCategoryEnum::GOLDEN_VISA->value => VisaCategoryEnum::GOLDEN_VISA->value,
            $mc === MemberCategoryEnum::DEPENDENT_CHILD->value && $months !== null && $months <= 12 => VisaCategoryEnum::NEWBORN_BORN_IN_UAE->value,
            $mc === MemberCategoryEnum::DEPENDENT_CHILD->value => VisaCategoryEnum::SPONSORED_EMPLOYER_FAMILY->value,
            default => $hqr->visa_category_id,
        };

        $hqr->salary_band_id = $salaryBand;
        $hqr->visa_category_id = $visa;
        $hqr->save();
    }

    private function applyMemberRelationSalaryAndVisa(HealthQuote $hqr): void
    {
        $this->queries->healthMembersBaseQuery($hqr)
            ->where('customer_type', 'Individual')
            ->where('is_third_party_payer', false)
            ->get()
            ->each(function (CustomerMembers $cm) use ($hqr) {
                $mc = (int) $cm->member_category_id;
                $months = $this->context->monthsSinceDob($this->context->dobToDateString($cm->dob));

                if ($cm->is_policy_holder) {
                    $cm->relation_code = null;
                    $cm->salary_band_id = $hqr->salary_band_id;
                    $cm->visa_category_id = $hqr->visa_category_id;
                    $cm->save();

                    return;
                }

                $relation = match (true) {
                    $mc === MemberCategoryEnum::DOMESTIC_WORKER->value => 'relDomesticWorker',
                    in_array($mc, [MemberCategoryEnum::EMPLOYEE_2->value, MemberCategoryEnum::EMPLOYEE_1->value, MemberCategoryEnum::SELF_EMPLOYED_FREELANCE->value, MemberCategoryEnum::INVESTOR_PARTNER->value, MemberCategoryEnum::GOLDEN_VISA->value], true) => null,
                    $mc === MemberCategoryEnum::DEPENDENT_SIBLING_OR_OTHER_RELATIVES->value => 'relSiblingOrRelatives',
                    $mc === MemberCategoryEnum::DEPENDENT_PARENT->value => 'relParent',
                    $mc === MemberCategoryEnum::DEPENDENT_SPOUSE->value => 'relSpouse',
                    $mc === MemberCategoryEnum::DEPENDENT_CHILD->value => 'relChild',
                    default => $cm->relation_code,
                };

                $salary = match ($mc) {
                    MemberCategoryEnum::DOMESTIC_WORKER->value => SalaryBandEnum::BELOW_OR_EQ_4000->value,
                    MemberCategoryEnum::EMPLOYEE_2->value => SalaryBandEnum::BETWEEN_4001_AND_12000->value,
                    MemberCategoryEnum::EMPLOYEE_1->value => SalaryBandEnum::BELOW_OR_EQ_4000->value,
                    MemberCategoryEnum::SELF_EMPLOYED_FREELANCE->value,
                    MemberCategoryEnum::INVESTOR_PARTNER->value,
                    MemberCategoryEnum::GOLDEN_VISA->value => SalaryBandEnum::ABOVE_12000->value,
                    MemberCategoryEnum::DEPENDENT_SIBLING_OR_OTHER_RELATIVES->value,
                    MemberCategoryEnum::DEPENDENT_PARENT->value,
                    MemberCategoryEnum::DEPENDENT_CHILD->value,
                    MemberCategoryEnum::DEPENDENT_SPOUSE->value => SalaryBandEnum::NO_SALARY_DEPENDENTS_OR_CHILDREN->value,
                    default => $cm->salary_band_id,
                };

                $visa = match (true) {
                    in_array($mc, [MemberCategoryEnum::DOMESTIC_WORKER->value, MemberCategoryEnum::EMPLOYEE_2->value, MemberCategoryEnum::EMPLOYEE_1->value, MemberCategoryEnum::DEPENDENT_SIBLING_OR_OTHER_RELATIVES->value, MemberCategoryEnum::DEPENDENT_PARENT->value, MemberCategoryEnum::DEPENDENT_SPOUSE->value], true) => VisaCategoryEnum::SPONSORED_EMPLOYER_FAMILY->value,
                    $mc === MemberCategoryEnum::SELF_EMPLOYED_FREELANCE->value => VisaCategoryEnum::SELF_EMPLOYED_FREELANCE->value,
                    $mc === MemberCategoryEnum::INVESTOR_PARTNER->value => VisaCategoryEnum::INVESTOR_PARTNER->value,
                    $mc === MemberCategoryEnum::GOLDEN_VISA->value => VisaCategoryEnum::GOLDEN_VISA->value,
                    $mc === MemberCategoryEnum::DEPENDENT_CHILD->value && $months !== null && $months <= 12 => VisaCategoryEnum::NEWBORN_BORN_IN_UAE->value,
                    $mc === MemberCategoryEnum::DEPENDENT_CHILD->value => VisaCategoryEnum::SPONSORED_EMPLOYER_FAMILY->value,
                    default => $cm->visa_category_id,
                };

                $cm->relation_code = $relation;
                $cm->salary_band_id = $salary;
                $cm->visa_category_id = $visa;
                $cm->save();
            });
    }

    private function applyHealthQuoteMemberCategoryRemap(HealthQuote $hqr): void
    {
        $dobStr = $this->context->dobToDateString($hqr->dob);
        $months = $this->context->monthsSinceDob($dobStr);
        $nid = (int) $hqr->nationality_id;
        $eid = $hqr->emirate_of_your_visa_id;
        $gccNationalityIds = app(NationalityService::class)->getGCCNationalityIds();
        $uaeNationalityIds = app(NationalityService::class)->getUAENationalityIds();

        $newMc = match (true) {
            $dobStr && $months !== null && $months <= 12 => MemberCategoryEnum::NEWBORN->value,
            in_array($nid, $uaeNationalityIds, true) => MemberCategoryEnum::UAE_NATIONAL->value,
            in_array($nid, $gccNationalityIds, true) => MemberCategoryEnum::GCC_NATIONAL->value,
            $eid !== null && (int) $eid === EmirateEnum::DUBAI => MemberCategoryEnum::EXPAT_DUBAI_VISA->value,
            $eid !== null && (int) $eid !== EmirateEnum::DUBAI => MemberCategoryEnum::EXPAT_NON_DUBAI_VISA->value,
            default => $hqr->member_category_id,
        };

        $hqr->member_category_id = $newMc;
        $hqr->save();
    }

    private function applyCustomerMemberCategoryRemap(HealthQuote $hqr): void
    {
        $gccNationalityIds = app(NationalityService::class)->getGCCNationalityIds();
        $uaeNationalityIds = app(NationalityService::class)->getUAENationalityIds();

        $this->queries->healthMembersBaseQuery($hqr)
            ->where('customer_type', 'Individual')
            ->where('is_third_party_payer', false)
            ->get()
            ->each(function (CustomerMembers $cm) use ($hqr, $gccNationalityIds, $uaeNationalityIds) {
                if ($cm->is_principal) {
                    $cm->member_category_id = $hqr->member_category_id;
                    $cm->save();

                    return;
                }

                $dobStr = $this->context->dobToDateString($cm->dob);
                $months = $this->context->monthsSinceDob($dobStr);
                $nid = (int) $cm->nationality_id;
                $eid = $cm->emirate_of_your_visa_id;

                $newMc = match (true) {
                    $dobStr && $months !== null && $months <= 12 => MemberCategoryEnum::NEWBORN->value,
                    in_array($nid, $uaeNationalityIds, true) => MemberCategoryEnum::UAE_NATIONAL->value,
                    in_array($nid, $gccNationalityIds, true) => MemberCategoryEnum::GCC_NATIONAL->value,
                    $eid !== null && (int) $eid === EmirateEnum::DUBAI => MemberCategoryEnum::EXPAT_DUBAI_VISA->value,
                    $eid !== null && (int) $eid !== EmirateEnum::DUBAI => MemberCategoryEnum::EXPAT_NON_DUBAI_VISA->value,
                    default => $cm->member_category_id,
                };

                $cm->member_category_id = $newMc;
                $cm->save();
            });
    }
}
