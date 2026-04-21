<?php

declare(strict_types=1);

use App\Enums\CustomerTypeEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\CustomerInsured;
use App\Models\HealthQuote;
use App\Models\Insured;
use App\Services\HealthQuoteRevampMigrationService;
use App\Services\HealthRevamp\HealthQuoteRevampMigrationContext;
use Carbon\Carbon;
use Tests\Helpers\TestSchemaCreator;

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

        $context = new HealthQuoteRevampMigrationContext;

        expect($context->isEntityHealthLead($quote->fresh()))->toBeTrue();
    });

    it('is false when active insured is Individual', function () {
        $quote = HealthQuote::factory()->create();
        $insured = Insured::factory()->create();

        CustomerInsured::factory()
            ->forActiveHealthLink($quote, $insured)
            ->create();

        $context = new HealthQuoteRevampMigrationContext;

        expect($context->isEntityHealthLead($quote->fresh()))->toBeFalse();
    });

    it('is false when customer_id is null', function () {
        $quote = HealthQuote::factory()->make(['customer_id' => null]);

        $context = new HealthQuoteRevampMigrationContext;

        expect($context->isEntityHealthLead($quote))->toBeFalse();
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

describe('allowedCustomerTypesForQuote', function () {
    it('defaults to Individual when there are no customer_insured rows', function () {
        $quote = HealthQuote::factory()->create();

        $context = new HealthQuoteRevampMigrationContext;

        expect($context->allowedCustomerTypesForQuote($quote))->toBe(['Individual']);
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

        $context = new HealthQuoteRevampMigrationContext;

        expect($context->allowedCustomerTypesForQuote($quote->fresh()))
            ->toEqualCanonicalizing([CustomerTypeEnum::Individual, CustomerTypeEnum::Entity]);
    });
});
