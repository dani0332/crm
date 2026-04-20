<?php

namespace App\Services;

use App\Enums\CustomerTypeEnum;
use App\Enums\EmirateEnum;
use App\Enums\HealthCoverForEnum;
use App\Enums\HealthInsureEnum;
use App\Enums\HealthPolicyHolderEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\MemberCategoryEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\SalaryBandEnum;
use App\Enums\VisaCategoryEnum;
use App\Models\CustomerInsured;
use App\Models\CustomerMembers;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;
use App\Services\Life\NationalityService;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Exception;

/**
 * Applies health revamp data migration logic (health-revamp-3-migrationScript.sql) to a single lead.
 * Intentionally omits filters on is_quote_locked and quote_status_id so locked / any status can be migrated.
 *
 * Skips entirely when the lead matches “entity health lead” semantics (active health customer_insured
 * row whose insured.customer_type is Entity), consistent with exclusion via health_revamp_bak_entity_health_leads.
 */
class HealthQuoteRevampMigrationService
{
    private const QUOTE_MORPH = HealthQuote::class;

    /**
     * @var list<string>
     */
    private const HEALTH_QUOTE_LOG_ATTRIBUTES = [
        'id',
        'cover_for_id',
        'insure_code',
        'policy_holder_code',
        'gender',
        'marital_status_id',
        'policy_holder_category_code',
        'salary_band_id',
        'visa_category_id',
        'member_category_id',
    ];

    /**
     * @var list<string>
     */
    private const CUSTOMER_MEMBER_LOG_ATTRIBUTES = [
        'id',
        'customer_type',
        'code',
        'first_name',
        'last_name',
        'is_principal',
        'is_policy_holder',
        'is_insured',
        'is_third_party_payer',
        'is_pec_marked',
        'gender',
        'marital_status_id',
        'relation_code',
        'salary_band_id',
        'visa_category_id',
        'member_category_id',
        'emirate_of_your_visa_id',
    ];

    public function getMirationStatuses(): array
    {
        return [
            QuoteStatusEnum::Draft,
            QuoteStatusEnum::Quoted,
            QuoteStatusEnum::AMLScreeningCleared,
            QuoteStatusEnum::AMLScreeningFailed,
            QuoteStatusEnum::NewLead,
            QuoteStatusEnum::Fake,
            QuoteStatusEnum::FTCSent,
            QuoteStatusEnum::FTCAccepted,
            QuoteStatusEnum::FTCResubmitted,
            QuoteStatusEnum::Lost,
            QuoteStatusEnum::KYCCleared,
            QuoteStatusEnum::FTCPending,
            QuoteStatusEnum::FollowedUp,
            QuoteStatusEnum::InNegotiation,
            QuoteStatusEnum::ApplicationPending,
            QuoteStatusEnum::PaymentPending,
            QuoteStatusEnum::QualificationPending,
            QuoteStatusEnum::Qualified,
            QuoteStatusEnum::TransactionDeclined,
            QuoteStatusEnum::ApplicationSubmitted,
            QuoteStatusEnum::Duplicate,
            QuoteStatusEnum::PriceTooHigh,
            QuoteStatusEnum::NotContactablePe,
            QuoteStatusEnum::FollowupCall,
            QuoteStatusEnum::Interested,
            QuoteStatusEnum::NoAnswer,
            QuoteStatusEnum::NotInterested,
            QuoteStatusEnum::NotEligibleForInsurance,
            QuoteStatusEnum::IMRenewal,
            QuoteStatusEnum::PendingQuote,
            QuoteStatusEnum::Uncontactable,
            QuoteStatusEnum::AFIA_RENEWAL,
            QuoteStatusEnum::Stale,
            QuoteStatusEnum::Allocated,
            QuoteStatusEnum::RenewalTermsReceived,
            QuoteStatusEnum::PendingRenewalInformation,
            QuoteStatusEnum::AdditionalInformationRequested,
            QuoteStatusEnum::QuoteRequested,
            QuoteStatusEnum::FinalizingTerms,
            QuoteStatusEnum::SentForTransactionApproval,
            QuoteStatusEnum::RenewalTermsSent,
            QuoteStatusEnum::EarlyRenewal,
            QuoteStatusEnum::PaymentLinkRequestedByCustomer,
            QuoteStatusEnum::PAYMENT_LINK_IN_PROGRESS,
            QuoteStatusEnum::PaymentLinkSentToCustomer,
            QuoteStatusEnum::PaymentInitiated,
            QuoteStatusEnum::PendingBorRequest,
        ];
    }

    public function isMigrated(HealthQuote $healthQuote): bool
    {
        return $healthQuote->insure_code !== null && $healthQuote->policy_holder_code !== null;
    }

    public function migrateLead(HealthQuote $healthQuote): void
    {
        if (! $healthQuote || $this->isMigrated($healthQuote)) {
            return;
        }

        if ($this->isEntityHealthLead($healthQuote)) {
            return;
        }

        LoggerService::startQuoteLogging($healthQuote->code, LoggerFeatureEnum::HEALTH_QUOTE_REVAMP);

        try {
            $beforeSnapshots = $this->logHealthMigrationLeadStateBefore($healthQuote);
            $this->fillMissingPrincipals($healthQuote);
            $this->updatePolicyHoldersFromNameMatch($healthQuote);
            $this->insertIndividualPolicyHolderWhenNoNameMatch($healthQuote);
            $this->insertMembersWhenNoneAndNoActiveInsured($healthQuote);
            $this->insertMembersWhenNoneAndInsuredIndividual($healthQuote);
            $this->applyCoverForIdUpdates($healthQuote);
            $this->applyInsureAndPolicyHolderCodes($healthQuote);
            $this->applyMaritalStatusUpdates($healthQuote);
            $this->normalizeGenderValues($healthQuote);
            $this->applyPolicyHolderCategoryCode($healthQuote);
            $this->applyHealthQuoteSalaryBandAndVisaFromMemberCategory($healthQuote);
            $this->applyMemberRelationSalaryAndVisa($healthQuote);
            $this->applyHealthQuoteMemberCategoryRemap($healthQuote);
            $this->applyCustomerMemberCategoryRemap($healthQuote);
            $this->logHealthMigrationLeadStateAfter(
                $healthQuote,
                $beforeSnapshots['health_quote'],
                $beforeSnapshots['customer_members'],
            );
        } catch (Exception $e) {
            LoggerService::error('Error migrating health quote', ['error' => $e->getMessage()]);

            return;
        } finally {
            LoggerService::endLogging();
        }
    }

    /**
     * @return array{health_quote: array<string, mixed>, customer_members: array<int|string, array<string, mixed>>}
     */
    private function logHealthMigrationLeadStateBefore(HealthQuote $healthQuote): array
    {
        $beforeQuote = $this->snapshotHealthQuoteAttributes($healthQuote);
        $beforeMembers = $this->snapshotCustomerMembers($healthQuote);

        LoggerService::info('Health quote revamp migration: lead state before', extra: $this->buildHealthMigrationLogContext(
            $healthQuote,
            $beforeQuote,
            $beforeMembers
        ));

        return [
            'health_quote' => $beforeQuote,
            'customer_members' => $beforeMembers,
        ];
    }

    /**
     * @param  array<string, mixed>  $beforeQuote
     * @param  array<int|string, array<string, mixed>>  $beforeMembers
     */
    private function logHealthMigrationLeadStateAfter(
        HealthQuote $healthQuote,
        array $beforeQuote,
        array $beforeMembers,
    ): void {
        $healthQuote = $healthQuote->fresh();
        $afterQuote = $this->snapshotHealthQuoteAttributes($healthQuote);
        $afterMembers = $this->snapshotCustomerMembers($healthQuote);

        $changed = array_filter([
            'health_quote' => $this->diffLogAttributeMaps($beforeQuote, $afterQuote),
            'customer_members' => $this->diffCustomerMemberSnapshots($beforeMembers, $afterMembers),
        ]);

        $afterContext = $this->buildHealthMigrationLogContext(
            $healthQuote,
            $afterQuote,
            $afterMembers
        );
        if ($changed !== []) {
            $afterContext['changed'] = $changed;
        }

        LoggerService::info('Health quote revamp migration: lead state after', extra: $afterContext);
    }

    private function healthMembersBaseQuery(HealthQuote $hqr)
    {
        return $this->healthMembersBaseQueryByQuoteId((int) $hqr->id);
    }

    private function healthMembersBaseQueryByQuoteId(int $quoteId)
    {
        return CustomerMembers::query()
            ->where('quote_type', self::QUOTE_MORPH)
            ->where('quote_id', $quoteId)
            ->whereNull('deleted_at');
    }

    private function nextIndividualCodeSuffix(int $customerEntityId): int
    {
        return 1 + CustomerMembers::query()
            ->where('customer_entity_id', $customerEntityId)
            ->where('customer_type', 'Individual')
            ->whereNull('deleted_at')
            ->count();
    }

    protected function monthsSinceDob(?string $dob): ?int
    {
        if ($dob === null || $dob === '') {
            return null;
        }

        return Carbon::parse($dob)->diffInMonths(Carbon::now());
    }

    /**
     * @param  mixed  $dob  Raw attribute (string, Carbon, etc.)
     */
    protected function dobToDateString(mixed $dob): ?string
    {
        if ($dob === null || $dob === '') {
            return null;
        }

        return Carbon::parse($dob)->format('Y-m-d');
    }

    /**
     * Same notion as health_revamp_bak_entity_health_leads in health-revamp-2-migrationScript-backup.sql.
     */
    protected function isEntityHealthLead(HealthQuote $hqr): bool
    {
        if ($hqr->customer_id === null) {
            return false;
        }

        $insured = $hqr->latestInsured()
            ->where('customer_insured.customer_id', $hqr->customer_id)
            ->first();

        return $insured !== null && $insured->customer_type === CustomerTypeEnum::Entity;
    }

    protected function memberIsAtLeastYearsOld(mixed $dob, int $years = 18): bool
    {
        if ($dob === null || $dob === '') {
            return false;
        }

        return Carbon::parse($dob)->age >= $years;
    }

    /**
     * @return list<string>
     */
    protected function allowedCustomerTypesForQuote(HealthQuote $hqr): array
    {
        $types = CustomerInsured::query()
            ->where('quote_request_id', $hqr->id)
            ->where('quote_type_id', QuoteTypeId::Health)
            ->where('is_active', true)
            ->join('insured', 'insured.id', '=', 'customer_insured.insured_id')
            ->distinct()
            ->pluck('insured.customer_type')
            ->filter()
            ->values()
            ->all();

        if ($types === []) {
            return ['Individual'];
        }

        return $types;
    }

    private function fillMissingPrincipals(HealthQuote $hqr): void
    {
        $members = $this->healthMembersBaseQuery($hqr)
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
        $members = $this->healthMembersBaseQuery($hqr)
            ->where('customer_type', 'Individual')
            ->where('is_third_party_payer', false)
            ->get();

        if ($members->isEmpty() || $members->contains(fn (CustomerMembers $m) => $m->is_policy_holder)) {
            return;
        }

        $nameMatches = $members->filter(function (CustomerMembers $m) use ($hqr) {
            return $m->first_name === $hqr->first_name
                && $m->last_name === $hqr->last_name
                && $this->memberIsAtLeastYearsOld($m->dob, 18);
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
        if ($hqr->customer_id === null) {
            return;
        }

        $hasMembers = $this->healthMembersBaseQuery($hqr)
            ->where('customer_type', 'Individual')
            ->where('is_third_party_payer', false)
            ->exists();

        if (! $hasMembers) {
            return;
        }

        $hasAnyPolicyHolder = $this->healthMembersBaseQuery($hqr)
            ->where('customer_type', 'Individual')
            ->where('is_third_party_payer', false)
            ->where('is_policy_holder', true)
            ->exists();

        if ($hasAnyPolicyHolder) {
            return;
        }

        $hasAdultNameMatchPolicyHolder = $this->healthMembersBaseQuery($hqr)
            ->where('customer_type', 'Individual')
            ->where('is_third_party_payer', false)
            ->where('is_policy_holder', true)
            ->where('first_name', $hqr->first_name)
            ->where('last_name', $hqr->last_name)
            ->whereNotNull('dob')
            ->get()
            ->contains(fn (CustomerMembers $m) => $this->memberIsAtLeastYearsOld($m->dob, 18));

        if ($hasAdultNameMatchPolicyHolder) {
            return;
        }

        $code = 'IND-'.$hqr->customer_id.'-'.$this->nextIndividualCodeSuffix($hqr->customer_id);

        CustomerMembers::query()->create([
            'quote_type' => self::QUOTE_MORPH,
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

    private function insertMembersWhenNoneAndNoActiveInsured(HealthQuote $hqr): void
    {
        if ($hqr->customer_id === null) {
            return;
        }

        $hasMembers = $this->healthMembersBaseQuery($hqr)
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

        $code = 'IND-'.$hqr->customer_id.'-'.$this->nextIndividualCodeSuffix($hqr->customer_id);

        CustomerMembers::query()->create([
            'quote_type' => self::QUOTE_MORPH,
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

        $hasMembers = $this->healthMembersBaseQuery($hqr)
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

        $code = 'IND-'.$hqr->customer_id.'-'.$this->nextIndividualCodeSuffix($hqr->customer_id);

        CustomerMembers::query()->create([
            'quote_type' => self::QUOTE_MORPH,
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
        $allowedTypes = $this->allowedCustomerTypesForQuote($hqr);

        $members = $this->healthMembersBaseQuery($hqr)
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

        $this->healthMembersBaseQuery($hqr)
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

        $this->healthMembersBaseQuery($hqr)
            ->where('customer_type', 'Individual')
            ->where('is_third_party_payer', false)
            ->whereIn('gender', ['M', 'Male'])
            ->update(['gender' => 'M']);

        $this->healthMembersBaseQuery($hqr)
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

        $months = $this->monthsSinceDob($this->dobToDateString($hqr->dob));
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
        $this->healthMembersBaseQuery($hqr)
            ->where('customer_type', 'Individual')
            ->where('is_third_party_payer', false)
            ->get()
            ->each(function (CustomerMembers $cm) use ($hqr) {
                $mc = (int) $cm->member_category_id;
                $months = $this->monthsSinceDob($this->dobToDateString($cm->dob));

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
        $dobStr = $this->dobToDateString($hqr->dob);
        $months = $this->monthsSinceDob($dobStr);
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

        $this->healthMembersBaseQuery($hqr)
            ->where('customer_type', 'Individual')
            ->where('is_third_party_payer', false)
            ->get()
            ->each(function (CustomerMembers $cm) use ($hqr, $gccNationalityIds, $uaeNationalityIds) {
                if ($cm->is_principal) {
                    $cm->member_category_id = $hqr->member_category_id;
                    $cm->save();

                    return;
                }

                $dobStr = $this->dobToDateString($cm->dob);
                $months = $this->monthsSinceDob($dobStr);
                $nid = (int) $cm->nationality_id;
                $eid = $cm->emirate_of_your_visa_id;

                $newMc = match (true) {
                    $dobStr && $months !== null && $months <= 12 => MemberCategoryEnum::NEWBORN->value,
                    in_array($nid, $uaeNationalityIds, true) => MemberCategoryEnum::UAE_NATIONAL->value,
                    in_array($nid, $gccNationalityIds, true) => MemberCategoryEnum::GCC_NATIONAL->value,
                    $eid !== null && (int) $eid === EmirateEnum::DUBAI => MemberCategoryEnum::EXPAT_DUBAI_VISA->value,
                    $eid === null || (int) $eid !== EmirateEnum::DUBAI => MemberCategoryEnum::EXPAT_NON_DUBAI_VISA->value,
                    default => $cm->member_category_id,
                };

                $cm->member_category_id = $newMc;
                $cm->save();
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotHealthQuoteAttributes(HealthQuote $healthQuote): array
    {
        return $healthQuote->only(self::HEALTH_QUOTE_LOG_ATTRIBUTES);
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    private function snapshotCustomerMembers(HealthQuote $healthQuote): array
    {
        $out = [];
        foreach (
            $healthQuote->members()
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->get() as $member
        ) {
            /** @var CustomerMembers $member */
            $out[$member->id] = $member->only(self::CUSTOMER_MEMBER_LOG_ATTRIBUTES);
        }

        return $out;
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $membersById
     * @return array<string, mixed>
     */
    private function buildHealthMigrationLogContext(HealthQuote $healthQuote, array $healthQuoteSnapshot, array $membersById): array
    {
        return [
            'code' => $healthQuote->code,
            'health_quote' => $healthQuoteSnapshot,
            'customer_members' => array_values($membersById),
        ];
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<string, array{before: mixed, after: mixed}>
     */
    private function diffLogAttributeMaps(array $before, array $after): array
    {
        $out = [];
        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
        foreach ($keys as $key) {
            $b = $before[$key] ?? null;
            $a = $after[$key] ?? null;
            if (json_encode($b) !== json_encode($a)) {
                $out[$key] = ['before' => $b, 'after' => $a];
            }
        }

        return $out;
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $beforeById
     * @param  array<int|string, array<string, mixed>>  $afterById
     * @return array<string, mixed>
     */
    private function diffCustomerMemberSnapshots(array $beforeById, array $afterById): array
    {
        $out = [];
        foreach ($afterById as $id => $afterRow) {
            if (! isset($beforeById[$id])) {
                $out[(string) $id] = [
                    'action' => 'created',
                    'after' => $afterRow,
                ];

                continue;
            }

            $rowDiff = $this->diffLogAttributeMaps($beforeById[$id], $afterRow);
            if ($rowDiff !== []) {
                $out[(string) $id] = $rowDiff;
            }
        }

        foreach ($beforeById as $id => $beforeRow) {
            if (! isset($afterById[$id])) {
                $out[(string) $id] = [
                    'action' => 'removed',
                    'before' => $beforeRow,
                ];
            }
        }

        return $out;
    }
}
