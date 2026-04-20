<?php

declare(strict_types=1);

use App\Enums\CustomerTypeEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\CustomerInsured;
use App\Models\HealthQuote;
use App\Models\Insured;
use App\Services\HealthQuoteRevampMigrationService;
use Carbon\Carbon;
use Tests\Helpers\TestSchemaCreator;
use Tests\Unit\Support\TestHealthQuoteRevampMigrationService;

beforeEach(function () {
    TestSchemaCreator::ensureMinimalSchema();
});

describe('getMirationStatuses', function () {
    it('includes draft, pending bor request, and excludes policy issued', function () {
        $service = new HealthQuoteRevampMigrationService;
        $statuses = $service->getMirationStatuses();

        expect($statuses)->toContain(QuoteStatusEnum::Draft)
            ->and($statuses)->toContain(QuoteStatusEnum::PendingBorRequest)
            ->and($statuses)->not->toContain(QuoteStatusEnum::PolicyIssued);
    });
});

describe('isMigrated', function () {
    it('is true when both insure_code and policy_holder_code are set', function () {
        $service = new HealthQuoteRevampMigrationService;
        $quote = HealthQuote::factory()->make([
            'insure_code' => 'ONLY_MYSELF',
            'policy_holder_code' => 'ME',
        ]);

        expect($service->isMigrated($quote))->toBeTrue();
    });

    it('is false when either code is missing', function ($insure, $policyHolder) {
        $service = new HealthQuoteRevampMigrationService;
        $quote = HealthQuote::factory()->make([
            'insure_code' => $insure,
            'policy_holder_code' => $policyHolder,
        ]);

        expect($service->isMigrated($quote))->toBeFalse();
    })->with([
        'missing insure' => [null, 'ME'],
        'missing policy holder' => ['ONLY_MYSELF', null],
        'both missing' => [null, null],
    ]);
});

describe('migrateLead', function () {
    it('returns without error when quote is already migrated', function () {
        $quote = HealthQuote::factory()->withRevampFields()->create();

        $service = new HealthQuoteRevampMigrationService;
        $service->migrateLead($quote);

        expect($quote->fresh()->insure_code)->not->toBeNull();
    });

    it('returns without error for entity health lead (skipped before migration work)', function () {
        $quote = HealthQuote::factory()->create();
        $insured = Insured::factory()->entity()->create();

        CustomerInsured::factory()
            ->forActiveHealthLink($quote, $insured)
            ->create();

        $service = new HealthQuoteRevampMigrationService;
        $service->migrateLead($quote->fresh());

        expect($quote->fresh()->insure_code)->toBeNull();
    });
});

describe('isEntityHealthLead', function () {
    it('is true when active health customer_insured points at an Entity insured', function () {
        $quote = HealthQuote::factory()->create();
        $insured = Insured::factory()->entity()->create();

        CustomerInsured::factory()
            ->forActiveHealthLink($quote, $insured)
            ->create();

        $service = new TestHealthQuoteRevampMigrationService;

        expect($service->exposeIsEntityHealthLead($quote->fresh()))->toBeTrue();
    });

    it('is false when active insured is Individual', function () {
        $quote = HealthQuote::factory()->create();
        $insured = Insured::factory()->create();

        CustomerInsured::factory()
            ->forActiveHealthLink($quote, $insured)
            ->create();

        $service = new TestHealthQuoteRevampMigrationService;

        expect($service->exposeIsEntityHealthLead($quote->fresh()))->toBeFalse();
    });

    it('is false when customer_id is null', function () {
        $quote = HealthQuote::factory()->make(['customer_id' => null]);

        $service = new TestHealthQuoteRevampMigrationService;

        expect($service->exposeIsEntityHealthLead($quote))->toBeFalse();
    });
});

describe('memberIsAtLeastYearsOld', function () {
    it('matches calendar age against the threshold', function () {
        $service = new TestHealthQuoteRevampMigrationService;

        Carbon::setTestNow(Carbon::parse('2026-06-15'));

        expect($service->exposeMemberIsAtLeastYearsOld('2008-06-16', 18))->toBeFalse()
            ->and($service->exposeMemberIsAtLeastYearsOld('2008-06-15', 18))->toBeTrue()
            ->and($service->exposeMemberIsAtLeastYearsOld(null, 18))->toBeFalse();

        Carbon::setTestNow();
    });
});

describe('monthsSinceDob', function () {
    it('returns null for empty dob', function ($dob) {
        $service = new TestHealthQuoteRevampMigrationService;

        expect($service->exposeMonthsSinceDob($dob))->toBeNull();
    })->with([null, '']);

    it('returns month count for a valid dob', function () {
        Carbon::setTestNow(Carbon::parse('2026-06-15'));
        $service = new TestHealthQuoteRevampMigrationService;

        $months = $service->exposeMonthsSinceDob('2020-06-15');

        expect($months)->toBeInt()->and($months)->toBeGreaterThan(0);

        Carbon::setTestNow();
    });
});

describe('dobToDateString', function () {
    it('returns null for empty values', function ($dob) {
        $service = new TestHealthQuoteRevampMigrationService;

        expect($service->exposeDobToDateString($dob))->toBeNull();
    })->with([null, '']);

    it('normalizes input to Y-m-d', function () {
        $service = new TestHealthQuoteRevampMigrationService;

        expect($service->exposeDobToDateString('2000-01-15'))->toBe('2000-01-15');
    });
});

describe('allowedCustomerTypesForQuote', function () {
    it('defaults to Individual when there are no customer_insured rows', function () {
        $quote = HealthQuote::factory()->create();

        $service = new TestHealthQuoteRevampMigrationService;

        expect($service->exposeAllowedCustomerTypesForQuote($quote))->toBe(['Individual']);
    });

    it('returns distinct insured customer types for active health links', function () {
        $quote = HealthQuote::factory()->create();

        $insuredIndividual = Insured::factory()->create([
            'customer_type' => CustomerTypeEnum::Individual,
        ]);
        $insuredEntity = Insured::factory()->entity()->create();

        CustomerInsured::factory()
            ->forActiveHealthLink($quote, $insuredIndividual)
            ->create(['is_active' => true]);

        CustomerInsured::factory()
            ->forActiveHealthLink($quote, $insuredEntity)
            ->create(['is_active' => true]);

        $service = new TestHealthQuoteRevampMigrationService;

        expect($service->exposeAllowedCustomerTypesForQuote($quote->fresh()))
            ->toEqualCanonicalizing([CustomerTypeEnum::Individual, CustomerTypeEnum::Entity]);
    });
});
