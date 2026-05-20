<?php

declare(strict_types=1);

use App\Enums\HealthCoverForEnum;
use App\Enums\QuoteStatusEnum;
use App\Events\HealthQuoteMigration;
use App\Models\CustomerInsured;
use App\Models\HealthQuote;
use App\Models\Insured;
use App\Services\HealthQuoteRevampMigrationService;
use App\Services\HealthRevamp\HealthQuoteRevampMigrationContext;
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

    it('does not dispatch and logs warning when health quote is not found', function () {
        Event::fake();

        $service = new HealthQuoteRevampMigrationService;

        $service->dispatchForNewChildLead(999999, 'SUL-001');

        Event::assertNotDispatched(HealthQuoteMigration::class);
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
