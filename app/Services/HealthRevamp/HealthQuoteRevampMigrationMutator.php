<?php

namespace App\Services\HealthRevamp;

use App\Enums\CustomerTypeEnum;
use App\Enums\EmirateEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\HealthCoverForEnum;
use App\Enums\HealthInsureEnum;
use App\Enums\HealthPolicyHolderEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\MemberCategoryEnum;
use App\Enums\PolicyHolderCategoryCodeEnum;
use App\Enums\QuoteTypeId;
use App\Enums\RelationCodeEnum;
use App\Enums\SalaryBandEnum;
use App\Enums\VisaCategoryEnum;
use App\Models\CustomerMembers;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;
use App\Services\Life\NationalityService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class HealthQuoteRevampMigrationMutator
{
    private readonly array $gccNationalityIds;
    private readonly array $uaeNationalityIds;

    public function __construct(
        private readonly HealthQuoteRevampMigrationQueries $queries,
        private readonly HealthQuoteRevampMigrationContext $context,
        NationalityService $nationalityService,
    ) {
        $this->gccNationalityIds = $nationalityService->getGCCNationalityIds();
        $this->uaeNationalityIds = $nationalityService->getUAENationalityIds();
    }

    /**
     * Entry point — runs all migration steps in dependency order.
     * Pre-transaction steps insert missing members/principals that later steps rely on.
     * The transaction wraps all field-level updates so they commit or roll back atomically.
     */
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

    /**
     * Ensures exactly one member is flagged as principal.
     * Prefers the member whose name matches the quote holder; falls back to the first member by id.
     * No-ops when a principal already exists or there are no eligible members.
     */
    private function fillMissingPrincipals(HealthQuote $hqr): void
    {
        $members = $this->queries->healthMembersQuery($hqr)
            ->select(['id', 'is_principal', 'first_name', 'last_name'])
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

    /**
     * Promotes an adult name-matched member to policy holder when none exists yet.
     * Among candidates, prefers the principal; ties are broken by id ascending.
     * No-ops when a policy holder already exists or no adult name match is found.
     */
    private function updatePolicyHoldersFromNameMatch(HealthQuote $hqr): void
    {
        $members = $this->queries->healthMembersQuery($hqr)
            ->select(['id', 'is_principal', 'is_policy_holder', 'first_name', 'last_name', 'dob'])
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

    /**
     * Inserts a non-insured policy-holder member seeded from the quote holder's details
     * when members exist but none qualify as an adult name-match policy holder.
     * Covers the case where the quote holder is the payer but not the insured.
     */
    private function insertIndividualPolicyHolderWhenNoNameMatch(HealthQuote $hqr): void
    {
        if (! $this->shouldInsertIndividualPolicyHolderWhenNoNameMatch($hqr)) {
            return;
        }

        $this->createIndividualMember($hqr, [
            'is_insured' => false,
            'is_policy_holder' => true,
            'is_principal' => false,
        ]);
    }

    /**
     * Guard for insertIndividualPolicyHolderWhenNoNameMatch:
     * insert only when there are existing members but no adult name-match policy holder among them.
     */
    private function shouldInsertIndividualPolicyHolderWhenNoNameMatch(HealthQuote $hqr): bool
    {
        if ($hqr->customer_id === null) {
            return false;
        }

        return $this->queries->hasIndividualMembers($hqr)
            && ! $this->queries->hasAdultNameMatchPolicyHolder($hqr);
    }

    /**
     * Bootstraps a fully-insured principal member from the quote holder's details when the quote
     * has no members and no active customer_insured link.
     * Handles leads where no insured data was ever captured through the normal flow.
     */
    private function insertMembersWhenNoneAndNoActiveInsured(HealthQuote $hqr): void
    {
        if ($hqr->customer_id === null) {
            return;
        }

        if ($this->queries->hasIndividualMembers($hqr)) {
            return;
        }

        if ($this->queries->hasActiveHealthInsured($hqr)) {
            return;
        }

        $this->createIndividualMember($hqr, [
            'is_insured' => true,
            'is_policy_holder' => true,
            'is_principal' => true,
        ]);
    }

    /**
     * Bootstraps a fully-insured principal member from the quote holder's details when the quote
     * has no members but does have an active Individual customer_insured row.
     * Bridges the gap between legacy insured-only data and the revamp member structure.
     */
    private function insertMembersWhenNoneAndInsuredIndividual(HealthQuote $hqr): void
    {
        if ($hqr->customer_id === null) {
            return;
        }

        if ($this->queries->hasIndividualMembers($hqr)) {
            return;
        }

        $row = $this->queries->findActiveIndividualInsuredRow($hqr);

        if (! $row) {
            return;
        }

        $this->createIndividualMember($hqr, [
            'is_insured' => true,
            'is_policy_holder' => true,
            'is_principal' => true,
        ]);
    }

    /**
     * Inserts an Individual CustomerMembers row with a unique IND-{customerId}-{n} code.
     * The count and insert run inside a single transaction with a pessimistic lock on the
     * count query, preventing concurrent migrations for the same customer from generating
     * duplicate codes.
     *
     * @param  array{is_insured: bool, is_policy_holder: bool, is_principal: bool}  $flags
     */
    private function createIndividualMember(HealthQuote $hqr, array $flags): void
    {
        DB::transaction(function () use ($hqr, $flags) {
            $suffix = 1 + CustomerMembers::query()
                ->where('customer_entity_id', $hqr->customer_id)
                ->where('customer_type', CustomerTypeEnum::Individual)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->count();

            CustomerMembers::query()->create(array_merge([
                'quote_type' => HealthQuoteRevampMigrationQueries::QUOTE_MORPH,
                'quote_id' => $hqr->id,
                'customer_entity_id' => $hqr->customer_id,
                'code' => 'IND-'.$hqr->customer_id.'-'.$suffix,
                'customer_type' => CustomerTypeEnum::Individual,
                'first_name' => $hqr->first_name,
                'last_name' => $hqr->last_name,
                'salary_band_id' => $hqr->salary_band_id,
                'is_pec_marked' => false,
                'visa_category_id' => $hqr->visa_category_id,
                'dob' => $hqr->dob ? Carbon::parse($hqr->dob)->format(config('constants.DATE_FORMAT_ONLY')) : null,
                'nationality_id' => $hqr->nationality_id,
                'is_third_party_payer' => false,
            ], $flags));
        });
    }

    /**
     * Consolidates legacy cover_for_id values to the revamp-compatible set.
     * INDIVIDUAL and FAMILY both map to INDIVIDUAL_AND_FAMILIES; domestic worker leads
     * are forced to DOMESTIC_HELPER regardless of the original value.
     */
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

    /**
     * Derives insure_code (ONLY_MYSELF vs MYSELF_AND_MY_FAMILY_MEMBERS) from the insured count,
     * and policy_holder_code (ME vs OTHER_ADULT_FAMILY_MEMBER) from whether the policy holder
     * is also an insured. Both codes are required by the revamp schema.
     */
    private function applyInsureAndPolicyHolderCodes(HealthQuote $hqr): void
    {
        $members = $this->queries->healthMembersQuery($hqr)
            ->select(['id', 'is_policy_holder', 'is_insured'])
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

    /**
     * Backfills marital_status_id on the quote and all Individual members from legacy gender codes.
     * M/F/FS/Female/Male → SINGLE; FM → MARRIED; UNMARRIED_PARTNER → SINGLE.
     * Principal members inherit the quote's resolved status; others derive from their own gender.
     */
    private function applyMaritalStatusUpdates(HealthQuote $hqr): void
    {
        $g = $hqr->gender;
        $msId = $hqr->marital_status_id;

        if ($msId === null && in_array($g, [GenericRequestEnum::MALE_SINGLE_VALUE, GenericRequestEnum::FEMALE_SHORT_VALUE, GenericRequestEnum::FEMALE_SINGLE_VALUE, GenericRequestEnum::FEMALE, GenericRequestEnum::MALE_SINGLE], true)) {
            $hqr->marital_status_id = MaritalStatusEnum::SINGLE->value;
        } elseif ($msId === null && $g === GenericRequestEnum::FEMALE_MARRIED_VALUE) {
            $hqr->marital_status_id = MaritalStatusEnum::MARRIED->value;
        } elseif ($msId !== null && (int) $msId === MaritalStatusEnum::UNMARRIED_PARTNER->value) {
            $hqr->marital_status_id = MaritalStatusEnum::SINGLE->value;
        }

        $hqr->save();

        $this->queries->healthMembersQuery($hqr)
            ->select(['id', 'is_principal', 'gender', 'marital_status_id'])
            ->get()
            ->each(function (CustomerMembers $cm) use ($hqr) {
                $g = $cm->gender;
                if ($cm->is_principal) {
                    $cm->marital_status_id = $hqr->marital_status_id;
                } elseif (! $cm->is_principal && in_array($g, [GenericRequestEnum::MALE_SINGLE_VALUE, GenericRequestEnum::FEMALE_SHORT_VALUE, GenericRequestEnum::FEMALE_SINGLE_VALUE, GenericRequestEnum::FEMALE, GenericRequestEnum::MALE_SINGLE], true)) {
                    $cm->marital_status_id = MaritalStatusEnum::SINGLE->value;
                } elseif (! $cm->is_principal && $g === GenericRequestEnum::FEMALE_MARRIED_VALUE) {
                    $cm->marital_status_id = MaritalStatusEnum::MARRIED->value;
                }
                $cm->save();
            });
    }

    /**
     * Collapses legacy multi-value gender codes to the canonical M/F values expected by the revamp.
     * Applied to the health_quote, personal_quote, and all Individual customer_members rows.
     */
    private function normalizeGenderValues(HealthQuote $hqr): void
    {
        $g = $hqr->gender;
        if (in_array($g, [GenericRequestEnum::MALE_SINGLE_VALUE, GenericRequestEnum::MALE_SINGLE], true)) {
            $hqr->gender = GenericRequestEnum::MALE_SINGLE_VALUE;
        } elseif (in_array($g, [GenericRequestEnum::FEMALE_SHORT_VALUE, GenericRequestEnum::FEMALE_SINGLE_VALUE, GenericRequestEnum::FEMALE, GenericRequestEnum::FEMALE_MARRIED_VALUE], true)) {
            $hqr->gender = GenericRequestEnum::FEMALE_SHORT_VALUE;
        }
        $hqr->save();

        PersonalQuote::query()
            ->where('quote_id', $hqr->id)
            ->where('quote_type_id', QuoteTypeId::Health)
            ->whereIn('gender', [GenericRequestEnum::MALE_SINGLE_VALUE, GenericRequestEnum::MALE_SINGLE])
            ->update(['gender' => GenericRequestEnum::MALE_SINGLE_VALUE]);

        PersonalQuote::query()
            ->where('quote_id', $hqr->id)
            ->where('quote_type_id', QuoteTypeId::Health)
            ->whereIn('gender', [GenericRequestEnum::FEMALE_SHORT_VALUE, GenericRequestEnum::FEMALE_SINGLE_VALUE, GenericRequestEnum::FEMALE, GenericRequestEnum::FEMALE_MARRIED_VALUE])
            ->update(['gender' => GenericRequestEnum::FEMALE_SHORT_VALUE]);

        $this->queries->healthMembersQuery($hqr)
            ->whereIn('gender', [GenericRequestEnum::MALE_SINGLE_VALUE, GenericRequestEnum::MALE_SINGLE])
            ->update(['gender' => GenericRequestEnum::MALE_SINGLE_VALUE]);

        $this->queries->healthMembersQuery($hqr)
            ->whereIn('gender', [GenericRequestEnum::FEMALE_SHORT_VALUE, GenericRequestEnum::FEMALE_SINGLE_VALUE, GenericRequestEnum::FEMALE, GenericRequestEnum::FEMALE_MARRIED_VALUE])
            ->update(['gender' => GenericRequestEnum::FEMALE_SHORT_VALUE]);
    }

    /**
     * Sets policy_holder_category_code based on the quote holder's nationality:
     * UAE national → UAE_CITIZEN, GCC national → GCC_CITIZEN, all others → RESIDENT.
     */
    private function applyPolicyHolderCategoryCode(HealthQuote $hqr): void
    {
        $nid = (int) $hqr->nationality_id;
        if (in_array($nid, $this->gccNationalityIds, true)) {
            $hqr->policy_holder_category_code = PolicyHolderCategoryCodeEnum::GCC_CITIZEN->value;
        } elseif (in_array($nid, $this->uaeNationalityIds, true)) {
            $hqr->policy_holder_category_code = PolicyHolderCategoryCodeEnum::UAE_CITIZEN->value;
        } else {
            $hqr->policy_holder_category_code = PolicyHolderCategoryCodeEnum::RESIDENT->value;
        }

        $hqr->save();
    }

    /**
     * Derives salary_band_id and visa_category_id on the health quote from member_category_id.
     * Newborns (≤ 12 months) get NEWBORN_BORN_IN_UAE visa; all other categories map via fixed rules.
     * Falls back to the existing value when the category has no explicit mapping.
     */
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
            $mc === MemberCategoryEnum::DEPENDENT_CHILD->value && $months !== null && $months <= 18 * 12 => null,
            $mc === MemberCategoryEnum::DEPENDENT_CHILD->value => VisaCategoryEnum::SPONSORED_EMPLOYER_FAMILY->value,
            default => $hqr->visa_category_id,
        };

        $hqr->salary_band_id = $salaryBand;
        $hqr->visa_category_id = $visa;
        $hqr->save();
    }

    /**
     * Sets relation_code, salary_band_id, and visa_category_id on each Individual member
     * from their member_category_id. Policy holders inherit salary/visa from the quote and
     * have their relation_code cleared. All other members derive values via category-based rules.
     */
    private function applyMemberRelationSalaryAndVisa(HealthQuote $hqr): void
    {
        $this->queries->healthMembersQuery($hqr)
            ->select(['id', 'is_policy_holder', 'member_category_id', 'dob', 'relation_code', 'salary_band_id', 'visa_category_id'])
            ->get()
            ->each(function (CustomerMembers $cm) use ($hqr) {
                $mc = (int) $cm->member_category_id;
                $months = $this->context->monthsSinceDob($this->context->dobToDateString($cm->dob));

                if ($cm->is_policy_holder) {
                    $cm->relation_code = RelationCodeEnum::SELF->value;
                    $cm->salary_band_id = $hqr->salary_band_id;
                    $cm->visa_category_id = $hqr->visa_category_id;
                    $cm->save();

                    return;
                }

                if ($hqr->member_category_id === MemberCategoryEnum::DOMESTIC_WORKER->value && $cm->is_principal) {
                    $cm->relation_code = RelationCodeEnum::DOMESTIC_WORKER->value;
                    $cm->salary_band_id = $hqr->salary_band_id;
                    $cm->visa_category_id = VisaCategoryEnum::SPONSORED_EMPLOYER_FAMILY->value;
                    $cm->save();

                    return;
                }

                $relation = match (true) {
                    $mc === MemberCategoryEnum::DOMESTIC_WORKER->value => RelationCodeEnum::DOMESTIC_WORKER->value,
                    in_array($mc, [MemberCategoryEnum::EMPLOYEE_2->value, MemberCategoryEnum::EMPLOYEE_1->value, MemberCategoryEnum::SELF_EMPLOYED_FREELANCE->value, MemberCategoryEnum::INVESTOR_PARTNER->value, MemberCategoryEnum::GOLDEN_VISA->value], true) => null,
                    $mc === MemberCategoryEnum::DEPENDENT_SIBLING_OR_OTHER_RELATIVES->value => RelationCodeEnum::RELATIVES->value,
                    $mc === MemberCategoryEnum::DEPENDENT_PARENT->value => RelationCodeEnum::PARENT->value,
                    $mc === MemberCategoryEnum::DEPENDENT_SPOUSE->value => RelationCodeEnum::SPOUSE->value,
                    $mc === MemberCategoryEnum::DEPENDENT_CHILD->value => RelationCodeEnum::CHILD->value,
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

    /**
     * Remaps member_category_id on the health quote to the revamp category set.
     * Priority: newborn (≤ 12 months) → UAE national → GCC national → Dubai expat → non-Dubai expat.
     * Falls back to the existing value when no condition matches.
     */
    private function applyHealthQuoteMemberCategoryRemap(HealthQuote $hqr): void
    {
        $dobStr = $this->context->dobToDateString($hqr->dob);
        $months = $this->context->monthsSinceDob($dobStr);
        $nid = (int) $hqr->nationality_id;
        $eid = $hqr->emirate_of_your_visa_id;
        $newMc = match (true) {
            $dobStr && $months !== null && $months <= 12 => MemberCategoryEnum::NEWBORN->value,
            in_array($nid, $this->uaeNationalityIds, true) => MemberCategoryEnum::UAE_NATIONAL->value,
            in_array($nid, $this->gccNationalityIds, true) => MemberCategoryEnum::GCC_NATIONAL->value,
            $eid !== null && (int) $eid === EmirateEnum::DUBAI => MemberCategoryEnum::EXPAT_DUBAI_VISA->value,
            $eid !== null && (int) $eid !== EmirateEnum::DUBAI => MemberCategoryEnum::EXPAT_NON_DUBAI_VISA->value,
            default => $hqr->member_category_id,
        };

        $hqr->member_category_id = $newMc;
        $hqr->save();
    }

    /**
     * Remaps member_category_id on each Individual member using the same priority rules as
     * applyHealthQuoteMemberCategoryRemap. The principal member inherits the quote's already-remapped
     * value; all others are evaluated independently from their own dob, nationality, and emirate.
     */
    private function applyCustomerMemberCategoryRemap(HealthQuote $hqr): void
    {
        $this->queries->healthMembersQuery($hqr)
            ->select(['id', 'is_principal', 'member_category_id', 'dob', 'nationality_id', 'emirate_of_your_visa_id'])
            ->get()
            ->each(function (CustomerMembers $cm) use ($hqr) {
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
                    in_array($nid, $this->uaeNationalityIds, true) => MemberCategoryEnum::UAE_NATIONAL->value,
                    in_array($nid, $this->gccNationalityIds, true) => MemberCategoryEnum::GCC_NATIONAL->value,
                    $eid !== null && (int) $eid === EmirateEnum::DUBAI => MemberCategoryEnum::EXPAT_DUBAI_VISA->value,
                    $eid !== null && (int) $eid !== EmirateEnum::DUBAI => MemberCategoryEnum::EXPAT_NON_DUBAI_VISA->value,
                    default => $cm->member_category_id,
                };

                $cm->member_category_id = $newMc;
                $cm->save();
            });
    }
}
