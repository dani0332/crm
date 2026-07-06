<?php

declare(strict_types=1);

use App\Enums\EmirateEnum;
use App\Enums\HealthCoverForEnum;
use App\Enums\HealthInsureEnum;
use App\Enums\HealthPolicyHolderEnum;
use App\Enums\MaritalStatusIdEnum;
use App\Enums\MemberCategoryEnum;
use App\Enums\PolicyHolderCategoryCodeEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RelationCodeEnum;
use App\Enums\SalaryBandEnum;
use App\Enums\VisaCategoryEnum;
use App\Events\HealthQuoteMigration;
use App\Models\CustomerInsured;
use App\Models\CustomerMembers;
use App\Models\HealthQuote;
use App\Models\Insured;
use App\Services\HealthQuoteRevampMigrationService;
use App\Services\HealthRevamp\HealthQuoteRevampMigrationContext;
use App\Services\HealthRevamp\HealthQuoteRevampMigrationMutator;
use App\Services\HealthRevamp\HealthQuoteRevampMigrationQueries;
use App\Services\Life\NationalityService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::ensureMinimalSchema();
});

describe('getMigrationStatuses', function () {
    it('includes draft, pending bor request, and excludes policy issued', function () {
        $service = new HealthQuoteRevampMigrationService;
        $statuses = $service->getMigrationStatuses();

        expect($statuses)->toContain(QuoteStatusEnum::Draft)
            ->and($statuses)->toContain(QuoteStatusEnum::PendingBorRequest)
            ->and($statuses)->not->toContain(QuoteStatusEnum::PolicyIssued);
    });
});

describe('isMigrated', function () {
    it('is true when cover_for_id is INDIVIDUAL_AND_FAMILIES (4)', function () {
        $service = new HealthQuoteRevampMigrationService;
        $quote = HealthQuote::factory()->make([
            'cover_for_id' => HealthCoverForEnum::INDIVIDUAL_AND_FAMILIES->value,
        ]);

        expect($service->isMigrated($quote))->toBeTrue();
    });

    it('is true when cover_for_id is DOMESTIC_HELPER (5)', function () {
        $service = new HealthQuoteRevampMigrationService;
        $quote = HealthQuote::factory()->make([
            'cover_for_id' => HealthCoverForEnum::DOMESTIC_HELPER->value,
        ]);

        expect($service->isMigrated($quote))->toBeTrue();
    });

    it('is false when cover_for_id is not 4 or 5', function (int $coverForId) {
        $service = new HealthQuoteRevampMigrationService;
        $quote = HealthQuote::factory()->make(['cover_for_id' => $coverForId]);

        expect($service->isMigrated($quote))->toBeFalse();
    })->with([
        'INDIVIDUAL (1)' => [HealthCoverForEnum::INDIVIDUAL->value],
        'FAMILY (2)' => [HealthCoverForEnum::FAMILY->value],
    ]);
});

describe('dispatchForLockedLead', function () {
    it('dispatches when status is in migration set and lead is locked', function () {
        Event::fake();

        $quote = HealthQuote::factory()->create(['is_quote_locked' => true]);
        $service = new HealthQuoteRevampMigrationService;

        $service->dispatchForLockedLead($quote->id, QuoteStatusEnum::Draft);

        Event::assertDispatched(HealthQuoteMigration::class, fn ($e) => $e->healthQuoteId === $quote->id);
    });

    it('does not dispatch when status is not in migration set', function () {
        Event::fake();

        $quote = HealthQuote::factory()->create(['is_quote_locked' => true]);
        $service = new HealthQuoteRevampMigrationService;

        $service->dispatchForLockedLead($quote->id, QuoteStatusEnum::PolicyIssued);

        Event::assertNotDispatched(HealthQuoteMigration::class);
    });

    it('does not dispatch when lead is not locked', function () {
        Event::fake();

        $quote = HealthQuote::factory()->create(['is_quote_locked' => false]);
        $service = new HealthQuoteRevampMigrationService;

        $service->dispatchForLockedLead($quote->id, QuoteStatusEnum::Draft);

        Event::assertNotDispatched(HealthQuoteMigration::class);
    });

    it('does not dispatch when quote is not found', function () {
        Event::fake();

        $service = new HealthQuoteRevampMigrationService;

        $service->dispatchForLockedLead(999999, QuoteStatusEnum::Draft);

        Event::assertNotDispatched(HealthQuoteMigration::class);
    });
});

describe('dispatchForNonEntityLead', function () {
    it('dispatches when not entity and status is in migration set', function () {
        Event::fake();

        $quote = HealthQuote::factory()->create();
        $service = new HealthQuoteRevampMigrationService;

        $service->dispatchForNonEntityLead($quote->id, QuoteStatusEnum::Draft, false);

        Event::assertDispatched(HealthQuoteMigration::class, fn ($e) => $e->healthQuoteId === $quote->id);
    });

    it('does not dispatch when isEntity is true', function () {
        Event::fake();

        $quote = HealthQuote::factory()->create();
        $service = new HealthQuoteRevampMigrationService;

        $service->dispatchForNonEntityLead($quote->id, QuoteStatusEnum::Draft, true);

        Event::assertNotDispatched(HealthQuoteMigration::class);
    });

    it('does not dispatch when status is not in migration set', function () {
        Event::fake();

        $quote = HealthQuote::factory()->create();
        $service = new HealthQuoteRevampMigrationService;

        $service->dispatchForNonEntityLead($quote->id, QuoteStatusEnum::PolicyIssued, false);

        Event::assertNotDispatched(HealthQuoteMigration::class);
    });
});

describe('dispatchForNewChildLead', function () {
    it('dispatches when health quote exists', function () {
        Event::fake();

        $quote = HealthQuote::factory()->create();
        $service = new HealthQuoteRevampMigrationService;

        $service->dispatchForNewChildLead($quote->id);

        Event::assertDispatched(HealthQuoteMigration::class, fn ($e) => $e->healthQuoteId === $quote->id);
    });

    it('does not dispatch when id is null', function () {
        Event::fake();

        $service = new HealthQuoteRevampMigrationService;

        $service->dispatchForNewChildLead(null);

        Event::assertNotDispatched(HealthQuoteMigration::class);
    });
});

describe('migrateLead', function () {
    it('returns without error when quote is already migrated', function () {
        $quote = HealthQuote::factory()->withRevampFields()->create();

        $service = new HealthQuoteRevampMigrationService;
        $service->migrateLead($quote->id);

        expect($quote->fresh()->insure_code)->not->toBeNull();
    });

    it('returns without error for entity health lead (skipped before migration work)', function () {
        $quote = HealthQuote::factory()->create();
        $insured = Insured::factory()->entity()->create();

        CustomerInsured::factory()
            ->forActiveHealthLink($quote, $insured)
            ->create();

        $service = new HealthQuoteRevampMigrationService;
        $service->migrateLead($quote->id);

        expect($quote->fresh()->insure_code)->toBeNull();
    });
});

describe('isEntity', function () {
    it('is true when active health customer_insured points at an Entity insured', function () {
        $quote = HealthQuote::factory()->create();
        $insured = Insured::factory()->entity()->create();

        CustomerInsured::factory()
            ->forActiveHealthLink($quote, $insured)
            ->create();

        expect($quote->fresh()->isEntity())->toBeTrue();
    });

    it('is false when active insured is Individual', function () {
        $quote = HealthQuote::factory()->create();
        $insured = Insured::factory()->create();

        CustomerInsured::factory()
            ->forActiveHealthLink($quote, $insured)
            ->create();

        expect($quote->fresh()->isEntity())->toBeFalse();
    });

    it('is false when customer_id is null', function () {
        $quote = HealthQuote::factory()->make(['customer_id' => null]);

        expect($quote->isEntity())->toBeFalse();
    });
});

describe('memberIsAtLeastYearsOld', function () {
    it('matches calendar age against the threshold', function () {
        $context = new HealthQuoteRevampMigrationContext;

        Carbon::setTestNow(Carbon::parse('2026-06-15'));

        expect($context->memberIsAtLeastYearsOld('2008-06-16', 18))->toBeFalse()
            ->and($context->memberIsAtLeastYearsOld('2008-06-15', 18))->toBeTrue()
            ->and($context->memberIsAtLeastYearsOld(null, 18))->toBeFalse();

        Carbon::setTestNow();
    });
});

describe('monthsSinceDob', function () {
    it('returns null for empty dob', function ($dob) {
        $context = new HealthQuoteRevampMigrationContext;

        expect($context->monthsSinceDob($dob))->toBeNull();
    })->with([null, '']);

    it('returns month count for a valid dob', function () {
        Carbon::setTestNow(Carbon::parse('2026-06-15'));
        $context = new HealthQuoteRevampMigrationContext;

        $months = $context->monthsSinceDob('2020-06-15');

        expect($months)->toBeInt()->and($months)->toBeGreaterThan(0);

        Carbon::setTestNow();
    });
});

describe('dobToDateString', function () {
    it('returns null for empty values', function ($dob) {
        $context = new HealthQuoteRevampMigrationContext;

        expect($context->dobToDateString($dob))->toBeNull();
    })->with([null, '']);

    it('normalizes input to Y-m-d', function () {
        $context = new HealthQuoteRevampMigrationContext;

        expect($context->dobToDateString('2000-01-15'))->toBe('2000-01-15');
    });
});

describe('applyPolicyHolderCategoryCode - nationality priority', function () {
    function makeMutatorWithNationalities(array $uaeIds, array $gccIds): HealthQuoteRevampMigrationMutator
    {
        $nationalityService = Mockery::mock(NationalityService::class);
        $nationalityService->shouldReceive('getUAENationalityIds')->andReturn($uaeIds);
        $nationalityService->shouldReceive('getGCCNationalityIds')->andReturn($gccIds);

        return new HealthQuoteRevampMigrationMutator(
            app(HealthQuoteRevampMigrationQueries::class),
            app(HealthQuoteRevampMigrationContext::class),
            $nationalityService,
        );
    }

    afterEach(fn () => Mockery::close());

    it('assigns UAE_CITIZEN when nationality is in the UAE list', function () {
        $uaeNationalityId = 42;
        $mutator = makeMutatorWithNationalities([$uaeNationalityId], [10, 11]);

        $quote = HealthQuote::factory()->create([
            'nationality_id' => $uaeNationalityId,
            'cover_for_id' => HealthCoverForEnum::INDIVIDUAL->value,
            'customer_id' => null,
        ]);

        $mutator->applyAll($quote);

        expect($quote->fresh()->policy_holder_category_code)
            ->toBe(PolicyHolderCategoryCodeEnum::UAE_CITIZEN->value);
    });

    it('assigns GCC_CITIZEN when nationality is in the GCC list but not UAE list', function () {
        $gccNationalityId = 10;
        $mutator = makeMutatorWithNationalities([], [$gccNationalityId]);

        $quote = HealthQuote::factory()->create([
            'nationality_id' => $gccNationalityId,
            'cover_for_id' => HealthCoverForEnum::INDIVIDUAL->value,
            'customer_id' => null,
        ]);

        $mutator->applyAll($quote);

        expect($quote->fresh()->policy_holder_category_code)
            ->toBe(PolicyHolderCategoryCodeEnum::GCC_CITIZEN->value);
    });

    it('assigns RESIDENT when nationality is in neither list', function () {
        $mutator = makeMutatorWithNationalities([1], [2]);

        $quote = HealthQuote::factory()->create([
            'nationality_id' => 99,
            'cover_for_id' => HealthCoverForEnum::INDIVIDUAL->value,
            'customer_id' => null,
        ]);

        $mutator->applyAll($quote);

        expect($quote->fresh()->policy_holder_category_code)
            ->toBe(PolicyHolderCategoryCodeEnum::RESIDENT->value);
    });
});

// ============================================================================
// SECTION: member insertion
// ============================================================================

describe('member insertion', function () {
    afterEach(fn () => Mockery::close());

    it('inserts a principal member when quote has no members and no active insured', function () {
        $mutator = makeMutatorWithNationalities([], []);

        $quote = HealthQuote::factory()->create([
            'cover_for_id' => HealthCoverForEnum::INDIVIDUAL->value,
            'customer_id' => 99,
        ]);

        $mutator->applyAll($quote);

        $member = CustomerMembers::where('quote_id', $quote->id)->first();
        expect($member)->not->toBeNull()
            ->and((bool) $member->is_principal)->toBeTrue()
            ->and((bool) $member->is_policy_holder)->toBeTrue()
            ->and((bool) $member->is_insured)->toBeTrue()
            ->and($member->code)->toStartWith('IND-');
    });

    it('inserts a member when quote has no members but has an active individual insured', function () {
        $mutator = makeMutatorWithNationalities([], []);

        $quote = HealthQuote::factory()->create([
            'cover_for_id' => HealthCoverForEnum::INDIVIDUAL->value,
            'customer_id' => 99,
        ]);

        $insured = Insured::factory()->create();
        CustomerInsured::factory()->forActiveHealthLink($quote, $insured)->create();

        $mutator->applyAll($quote);

        expect(CustomerMembers::where('quote_id', $quote->id)->count())->toBe(1);
    });
});

// ============================================================================
// SECTION: domestic worker migration
// ============================================================================

describe('domestic worker migration', function () {
    afterEach(fn () => Mockery::close());

    it('sets cover_for_id to DOMESTIC_HELPER and relation_code to DOMESTIC_WORKER on a principal non-policy-holder', function () {
        $mutator = makeMutatorWithNationalities([], []);

        $quote = HealthQuote::factory()->create([
            'cover_for_id' => HealthCoverForEnum::INDIVIDUAL->value,
            'member_category_id' => MemberCategoryEnum::DOMESTIC_WORKER->value,
            'first_name' => 'Employer',
            'last_name' => 'Test',
            'customer_id' => null,
        ]);

        $member = CustomerMembers::factory()->create([
            'quote_id' => $quote->id,
            'first_name' => 'Worker',
            'last_name' => 'Person',
            'member_category_id' => MemberCategoryEnum::DOMESTIC_WORKER->value,
            'is_principal' => true,
            'is_policy_holder' => false,
        ]);

        $mutator->applyAll($quote);

        expect((int) $quote->fresh()->cover_for_id)->toBe(HealthCoverForEnum::DOMESTIC_HELPER->value)
            ->and($member->fresh()->relation_code)->toBe(RelationCodeEnum::DOMESTIC_WORKER->value);
    });
});

// ============================================================================
// SECTION: gender normalization
// ============================================================================

describe('gender normalization', function () {
    afterEach(fn () => Mockery::close());

    it('normalizes Male to M', function () {
        $mutator = makeMutatorWithNationalities([], []);

        $quote = HealthQuote::factory()->create([
            'gender' => 'Male',
            'cover_for_id' => HealthCoverForEnum::INDIVIDUAL->value,
            'customer_id' => null,
        ]);

        $mutator->applyAll($quote);

        expect($quote->fresh()->gender)->toBe('M');
    });

    it('normalizes FM (married female) to F', function () {
        $mutator = makeMutatorWithNationalities([], []);

        $quote = HealthQuote::factory()->create([
            'gender' => 'FM',
            'cover_for_id' => HealthCoverForEnum::INDIVIDUAL->value,
            'customer_id' => null,
        ]);

        $mutator->applyAll($quote);

        expect($quote->fresh()->gender)->toBe('F');
    });
});

// ============================================================================
// SECTION: marital status derivation
// ============================================================================

describe('marital status derivation from gender', function () {
    afterEach(fn () => Mockery::close());

    it('sets MARRIED when gender is FM and marital_status_id is null', function () {
        $mutator = makeMutatorWithNationalities([], []);

        $quote = HealthQuote::factory()->create([
            'gender' => 'FM',
            'marital_status_id' => null,
            'cover_for_id' => HealthCoverForEnum::INDIVIDUAL->value,
            'customer_id' => null,
        ]);

        $mutator->applyAll($quote);

        expect($quote->fresh()->marital_status_id)->toBe(MaritalStatusIdEnum::MARRIED->value);
    });

    it('sets SINGLE when gender is M and marital_status_id is null', function () {
        $mutator = makeMutatorWithNationalities([], []);

        $quote = HealthQuote::factory()->create([
            'gender' => 'M',
            'marital_status_id' => null,
            'cover_for_id' => HealthCoverForEnum::INDIVIDUAL->value,
            'customer_id' => null,
        ]);

        $mutator->applyAll($quote);

        expect($quote->fresh()->marital_status_id)->toBe(MaritalStatusIdEnum::SINGLE->value);
    });
});

// ============================================================================
// SECTION: insure_code and policy_holder_code derivation
// ============================================================================

describe('insure_code and policy_holder_code derivation', function () {
    afterEach(fn () => Mockery::close());

    it('sets ONLY_MYSELF and ME when there is exactly one insured policy-holder', function () {
        $mutator = makeMutatorWithNationalities([], []);

        $quote = HealthQuote::factory()->create([
            'cover_for_id' => HealthCoverForEnum::INDIVIDUAL->value,
            'customer_id' => null,
        ]);

        CustomerMembers::factory()->create([
            'quote_id' => $quote->id,
            'is_insured' => true,
            'is_policy_holder' => true,
            'is_principal' => true,
        ]);

        $mutator->applyAll($quote);

        expect($quote->fresh()->insure_code)->toBe(HealthInsureEnum::ONLY_MYSELF->value)
            ->and($quote->fresh()->policy_holder_code)->toBe(HealthPolicyHolderEnum::ME->value);
    });

    it('sets MYSELF_AND_MY_FAMILY_MEMBERS when there are multiple insured members', function () {
        $mutator = makeMutatorWithNationalities([], []);

        $quote = HealthQuote::factory()->create([
            'cover_for_id' => HealthCoverForEnum::INDIVIDUAL->value,
            'customer_id' => null,
        ]);

        CustomerMembers::factory()->create([
            'quote_id' => $quote->id,
            'is_insured' => true,
            'is_policy_holder' => true,
            'is_principal' => true,
        ]);

        CustomerMembers::factory()->create([
            'quote_id' => $quote->id,
            'is_insured' => true,
            'is_policy_holder' => false,
            'is_principal' => false,
        ]);

        $mutator->applyAll($quote);

        expect($quote->fresh()->insure_code)->toBe(HealthInsureEnum::MYSELF_AND_MY_FAMILY_MEMBERS->value);
    });
});

// ============================================================================
// SECTION: member category remap
// ============================================================================

describe('member category remap', function () {
    afterEach(fn () => Mockery::close());

    it('remaps to NEWBORN when quote DOB is within 12 months', function () {
        Carbon::setTestNow(Carbon::parse('2026-05-20'));

        $mutator = makeMutatorWithNationalities([], []);

        $quote = HealthQuote::factory()->create([
            'dob' => '2026-01-01',
            'nationality_id' => 999,
            'emirate_of_your_visa_id' => null,
            'cover_for_id' => HealthCoverForEnum::INDIVIDUAL->value,
            'customer_id' => null,
        ]);

        $mutator->applyAll($quote);

        expect($quote->fresh()->member_category_id)->toBe(MemberCategoryEnum::NEWBORN->value);

        Carbon::setTestNow();
    });

    it('remaps to UAE_NATIONAL when nationality is in the UAE list', function () {
        $uaeNationalityId = 42;
        $mutator = makeMutatorWithNationalities([$uaeNationalityId], []);

        $quote = HealthQuote::factory()->create([
            'dob' => '1990-01-01',
            'nationality_id' => $uaeNationalityId,
            'cover_for_id' => HealthCoverForEnum::INDIVIDUAL->value,
            'customer_id' => null,
        ]);

        $mutator->applyAll($quote);

        expect($quote->fresh()->member_category_id)->toBe(MemberCategoryEnum::UAE_NATIONAL->value);
    });

    it('remaps to EXPAT_DUBAI_VISA when emirate is Dubai and nationality is not UAE/GCC', function () {
        $mutator = makeMutatorWithNationalities([], []);

        $quote = HealthQuote::factory()->create([
            'dob' => '1990-01-01',
            'nationality_id' => 999,
            'emirate_of_your_visa_id' => EmirateEnum::DUBAI,
            'cover_for_id' => HealthCoverForEnum::INDIVIDUAL->value,
            'customer_id' => null,
        ]);

        $mutator->applyAll($quote);

        expect($quote->fresh()->member_category_id)->toBe(MemberCategoryEnum::EXPAT_DUBAI_VISA->value);
    });
});

// ============================================================================
// SECTION: idempotency
// ============================================================================

describe('idempotency', function () {
    it('migrateLead is a no-op on an already-migrated quote', function () {
        $mutator = makeMutatorWithNationalities([], []);

        $quote = HealthQuote::factory()->create([
            'cover_for_id' => HealthCoverForEnum::INDIVIDUAL->value,
            'customer_id' => null,
        ]);

        $mutator->applyAll($quote);

        $stateAfterFirst = $quote->fresh()->only(['cover_for_id', 'policy_holder_category_code']);

        $service = new HealthQuoteRevampMigrationService;
        $service->migrateLead($quote->id);

        expect($quote->fresh()->only(['cover_for_id', 'policy_holder_category_code']))->toBe($stateAfterFirst);
    });
});

// ============================================================================
// SECTION: policy holder name-match assignment
// ============================================================================

describe('policy holder name-match assignment', function () {
    afterEach(fn () => Mockery::close());

    it('promotes an adult name-matched member to policy holder when none exists', function () {
        $mutator = makeMutatorWithNationalities([], []);

        $quote = HealthQuote::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'cover_for_id' => HealthCoverForEnum::INDIVIDUAL->value,
            'customer_id' => null,
        ]);

        $member = CustomerMembers::factory()->create([
            'quote_id' => $quote->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'dob' => '1990-01-01',
            'is_policy_holder' => false,
            'is_principal' => true,
        ]);

        $mutator->applyAll($quote);

        expect((bool) $member->fresh()->is_policy_holder)->toBeTrue();
    });
});

// ============================================================================
// SECTION: getSalaryBandId — isPolicyHolderInsured flag
// ============================================================================

describe('getSalaryBandId with isPolicyHolderInsured', function () {
    afterEach(fn () => Mockery::close());

    it('returns null for dependent categories when policy holder is insured', function (int $mc) {
        $mutator = makeMutatorWithNationalities([], []);

        $quote = HealthQuote::factory()->make(['member_category_id' => $mc]);

        expect($mutator->getSalaryBandId($quote, true))->toBeNull();
    })->with([
        'DEPENDENT_SPOUSE' => [MemberCategoryEnum::DEPENDENT_SPOUSE->value],
        'DEPENDENT_CHILD' => [MemberCategoryEnum::DEPENDENT_CHILD->value],
        'DEPENDENT_PARENT' => [MemberCategoryEnum::DEPENDENT_PARENT->value],
        'DEPENDENT_SIBLING_OR_OTHER_RELATIVES' => [MemberCategoryEnum::DEPENDENT_SIBLING_OR_OTHER_RELATIVES->value],
    ]);

    it('still returns NO_SALARY_DEPENDENTS_OR_CHILDREN for dependent categories when policy holder is NOT insured', function (int $mc) {
        $mutator = makeMutatorWithNationalities([], []);

        $quote = HealthQuote::factory()->make(['member_category_id' => $mc]);

        expect($mutator->getSalaryBandId($quote, false))
            ->toBe(SalaryBandEnum::NO_SALARY_DEPENDENTS_OR_CHILDREN->value);
    })->with([
        'DEPENDENT_SPOUSE' => [MemberCategoryEnum::DEPENDENT_SPOUSE->value],
        'DEPENDENT_CHILD' => [MemberCategoryEnum::DEPENDENT_CHILD->value],
    ]);

    it('returns the correct band for non-dependent categories regardless of isPolicyHolderInsured', function () {
        $mutator = makeMutatorWithNationalities([], []);

        $quoteEmployee = HealthQuote::factory()->make([
            'member_category_id' => MemberCategoryEnum::EMPLOYEE_1->value,
        ]);

        expect($mutator->getSalaryBandId($quoteEmployee, true))
            ->toBe(SalaryBandEnum::BELOW_OR_EQ_4000->value);
    });
});

// ============================================================================
// SECTION: applyPricingCorrectionsForMigratedLead
// ============================================================================

describe('applyPricingCorrectionsForMigratedLead (via migrateLead on already-migrated quote)', function () {
    it('corrects visa_category_id from SPONSORED_EMPLOYER_FAMILY to EMPLOYMENT on quote when policy holder is insured', function () {
        $quote = HealthQuote::factory()->create([
            'cover_for_id' => HealthCoverForEnum::INDIVIDUAL_AND_FAMILIES->value,
            'visa_category_id' => VisaCategoryEnum::SPONSORED_EMPLOYER_FAMILY->value,
            'salary_band_id' => SalaryBandEnum::NO_SALARY_DEPENDENTS_OR_CHILDREN->value,
        ]);

        CustomerMembers::factory()->create([
            'quote_id' => $quote->id,
            'is_policy_holder' => true,
            'is_insured' => true,
            'visa_category_id' => VisaCategoryEnum::SPONSORED_EMPLOYER_FAMILY->value,
            'salary_band_id' => SalaryBandEnum::NO_SALARY_DEPENDENTS_OR_CHILDREN->value,
        ]);

        $service = new HealthQuoteRevampMigrationService;
        $service->migrateLead($quote->id);

        expect((int) $quote->fresh()->visa_category_id)->toBe(VisaCategoryEnum::EMPLOYMENT->value);
    });

    it('nulls salary_band_id on quote when policy holder is insured and band is NO_SALARY_DEPENDENTS_OR_CHILDREN', function () {
        $quote = HealthQuote::factory()->create([
            'cover_for_id' => HealthCoverForEnum::INDIVIDUAL_AND_FAMILIES->value,
            'visa_category_id' => VisaCategoryEnum::SPONSORED_EMPLOYER_FAMILY->value,
            'salary_band_id' => SalaryBandEnum::NO_SALARY_DEPENDENTS_OR_CHILDREN->value,
        ]);

        CustomerMembers::factory()->create([
            'quote_id' => $quote->id,
            'is_policy_holder' => true,
            'is_insured' => true,
            'visa_category_id' => VisaCategoryEnum::SPONSORED_EMPLOYER_FAMILY->value,
            'salary_band_id' => SalaryBandEnum::NO_SALARY_DEPENDENTS_OR_CHILDREN->value,
        ]);

        $service = new HealthQuoteRevampMigrationService;
        $service->migrateLead($quote->id);

        expect($quote->fresh()->salary_band_id)->toBeNull();
    });

    it('corrects visa_category_id and salary_band_id on the policy holder member', function () {
        $quote = HealthQuote::factory()->create([
            'cover_for_id' => HealthCoverForEnum::INDIVIDUAL_AND_FAMILIES->value,
            'visa_category_id' => VisaCategoryEnum::SPONSORED_EMPLOYER_FAMILY->value,
            'salary_band_id' => SalaryBandEnum::NO_SALARY_DEPENDENTS_OR_CHILDREN->value,
        ]);

        $member = CustomerMembers::factory()->create([
            'quote_id' => $quote->id,
            'is_policy_holder' => true,
            'is_insured' => true,
            'visa_category_id' => VisaCategoryEnum::SPONSORED_EMPLOYER_FAMILY->value,
            'salary_band_id' => SalaryBandEnum::NO_SALARY_DEPENDENTS_OR_CHILDREN->value,
        ]);

        $service = new HealthQuoteRevampMigrationService;
        $service->migrateLead($quote->id);

        expect((int) $member->fresh()->visa_category_id)->toBe(VisaCategoryEnum::EMPLOYMENT->value)
            ->and($member->fresh()->salary_band_id)->toBeNull();
    });

    it('does not modify quote or members when no insured policy holder exists', function () {
        $quote = HealthQuote::factory()->create([
            'cover_for_id' => HealthCoverForEnum::INDIVIDUAL_AND_FAMILIES->value,
            'visa_category_id' => VisaCategoryEnum::SPONSORED_EMPLOYER_FAMILY->value,
            'salary_band_id' => SalaryBandEnum::NO_SALARY_DEPENDENTS_OR_CHILDREN->value,
        ]);

        // Policy holder is NOT insured
        CustomerMembers::factory()->create([
            'quote_id' => $quote->id,
            'is_policy_holder' => true,
            'is_insured' => false,
            'visa_category_id' => VisaCategoryEnum::SPONSORED_EMPLOYER_FAMILY->value,
            'salary_band_id' => SalaryBandEnum::NO_SALARY_DEPENDENTS_OR_CHILDREN->value,
        ]);

        $service = new HealthQuoteRevampMigrationService;
        $service->migrateLead($quote->id);

        expect((int) $quote->fresh()->visa_category_id)->toBe(VisaCategoryEnum::SPONSORED_EMPLOYER_FAMILY->value)
            ->and((int) $quote->fresh()->salary_band_id)->toBe(SalaryBandEnum::NO_SALARY_DEPENDENTS_OR_CHILDREN->value);
    });

    it('does not modify values that are already correct', function () {
        $quote = HealthQuote::factory()->create([
            'cover_for_id' => HealthCoverForEnum::INDIVIDUAL_AND_FAMILIES->value,
            'visa_category_id' => VisaCategoryEnum::EMPLOYMENT->value,
            'salary_band_id' => null,
        ]);

        CustomerMembers::factory()->create([
            'quote_id' => $quote->id,
            'is_policy_holder' => true,
            'is_insured' => true,
            'visa_category_id' => VisaCategoryEnum::EMPLOYMENT->value,
            'salary_band_id' => null,
        ]);

        $service = new HealthQuoteRevampMigrationService;
        $service->migrateLead($quote->id);

        expect((int) $quote->fresh()->visa_category_id)->toBe(VisaCategoryEnum::EMPLOYMENT->value)
            ->and($quote->fresh()->salary_band_id)->toBeNull();
    });
});
