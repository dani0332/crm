<?php

declare(strict_types=1);

use App\Enums\AmlAutomationStatus;
use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteStatusEnum;
use App\Jobs\AmlScreeningAutomationJob;
use App\Models\AmlAutomation;
use App\Models\ApplicationStorage;
use App\Models\TravelQuote;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Schema;
use Tests\Helpers\TestSchemaCreator;

const RETRIGGER_TRAVEL_AML_ENDPOINT = '/api/v1/imcrm/travel/retrigger-aml-screening';

beforeEach(function (): void {
    TestSchemaCreator::createMinimalSchema();

    Schema::dropIfExists('aml_automation');
    Schema::create('aml_automation', function (Blueprint $table): void {
        $table->id();
        $table->string('code')->unique();
        $table->string('status')->nullable();
        $table->text('result')->nullable();
        $table->timestamps();
    });

    ApplicationStorage::factory()->travelAmlRetriggerEnabled()->create();
});

afterEach(function (): void {
    Carbon::setTestNow(null);
});

it('returns 422 when start_date is missing', function (): void {
    $this->postJson(RETRIGGER_TRAVEL_AML_ENDPOINT, ['end_date' => '2026-06-01'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['start_date']);
});

it('returns 422 when end_date is missing', function (): void {
    $this->postJson(RETRIGGER_TRAVEL_AML_ENDPOINT, ['start_date' => '2026-06-01'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['end_date']);
});

it('returns 422 when start_date is after end_date', function (): void {
    $this->postJson(RETRIGGER_TRAVEL_AML_ENDPOINT, [
        'start_date' => '2026-06-10',
        'end_date' => '2026-06-01',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['start_date']);
});

it('returns 200 with zero dispatched when no matching quotes exist', function (): void {
    $this->postJson(RETRIGGER_TRAVEL_AML_ENDPOINT, [
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-31',
    ])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'data' => ['dispatched_count' => 0],
        ]);
});

it('does not dispatch jobs for quotes outside the date range', function (): void {
    Bus::fake();

    Carbon::setTestNow('2026-01-15 10:00:00');
    TravelQuote::factory()->create([
        'quote_status_id' => QuoteStatusEnum::PolicyBooked,
        'aml_status' => AMLStatusCode::AMLPending,
    ]);
    Carbon::setTestNow(null);

    $this->postJson(RETRIGGER_TRAVEL_AML_ENDPOINT, [
        'start_date' => '2026-02-01',
        'end_date' => '2026-02-28',
    ])
        ->assertOk()
        ->assertJson(['data' => ['dispatched_count' => 0]]);

    Bus::assertNothingDispatched();
});

it('dispatches jobs for quotes with null aml_status (treated as pending)', function (): void {
    Bus::fake();

    Carbon::setTestNow('2026-01-15 10:00:00');
    $quote = TravelQuote::factory()->create([
        'quote_status_id' => QuoteStatusEnum::PolicyBooked,
        'aml_status' => null,
    ]);
    Carbon::setTestNow(null);

    $this->postJson(RETRIGGER_TRAVEL_AML_ENDPOINT, [
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-31',
    ])
        ->assertOk()
        ->assertJson(['data' => ['dispatched_count' => 1]]);

    Bus::assertDispatched(AmlScreeningAutomationJob::class);

    expect(AmlAutomation::where('code', $quote->code)->value('status'))
        ->toBe(AmlAutomationStatus::Queue->value);
});

it('does not dispatch jobs for quotes with non-pending aml_status', function (): void {
    Bus::fake();

    Carbon::setTestNow('2026-01-15 10:00:00');
    TravelQuote::factory()->create([
        'quote_status_id' => QuoteStatusEnum::PolicyBooked,
        'aml_status' => AMLStatusCode::AMLScreeningCleared,
    ]);
    Carbon::setTestNow(null);

    $this->postJson(RETRIGGER_TRAVEL_AML_ENDPOINT, [
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-31',
    ])
        ->assertOk()
        ->assertJson(['data' => ['dispatched_count' => 0]]);

    Bus::assertNothingDispatched();
});

it('does not dispatch jobs for quotes with non-PolicyBooked status', function (): void {
    Bus::fake();

    Carbon::setTestNow('2026-01-15 10:00:00');
    TravelQuote::factory()->create([
        'quote_status_id' => QuoteStatusEnum::Draft,
        'aml_status' => AMLStatusCode::AMLPending,
    ]);
    Carbon::setTestNow(null);

    $this->postJson(RETRIGGER_TRAVEL_AML_ENDPOINT, [
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-31',
    ])
        ->assertOk()
        ->assertJson(['data' => ['dispatched_count' => 0]]);

    Bus::assertNothingDispatched();
});

it('dispatches jobs and upserts aml_automation for matching travel quotes', function (): void {
    Bus::fake();

    Carbon::setTestNow('2026-01-10 10:00:00');
    $quote1 = TravelQuote::factory()->create([
        'quote_status_id' => QuoteStatusEnum::PolicyBooked,
        'aml_status' => AMLStatusCode::AMLPending,
    ]);

    Carbon::setTestNow('2026-01-20 10:00:00');
    $quote2 = TravelQuote::factory()->create([
        'quote_status_id' => QuoteStatusEnum::PolicyBooked,
        'aml_status' => AMLStatusCode::AMLPending,
    ]);
    Carbon::setTestNow(null);

    $this->postJson(RETRIGGER_TRAVEL_AML_ENDPOINT, [
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-31',
    ])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'data' => ['dispatched_count' => 2],
        ]);

    Bus::assertDispatched(AmlScreeningAutomationJob::class, 2);

    expect(AmlAutomation::where('code', $quote1->code)->value('status'))
        ->toBe(AmlAutomationStatus::Queue->value);

    expect(AmlAutomation::where('code', $quote2->code)->value('status'))
        ->toBe(AmlAutomationStatus::Queue->value);
});

it('upserts existing aml_automation record to queue status before dispatching', function (): void {
    Bus::fake();

    Carbon::setTestNow('2026-01-15 10:00:00');
    $quote = TravelQuote::factory()->create([
        'quote_status_id' => QuoteStatusEnum::PolicyBooked,
        'aml_status' => AMLStatusCode::AMLPending,
    ]);
    Carbon::setTestNow(null);

    AmlAutomation::factory()->failed()->create(['code' => $quote->code]);

    $this->postJson(RETRIGGER_TRAVEL_AML_ENDPOINT, [
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-31',
    ])
        ->assertOk()
        ->assertJson(['data' => ['dispatched_count' => 1]]);

    Bus::assertDispatched(AmlScreeningAutomationJob::class);

    expect(AmlAutomation::where('code', $quote->code)->value('status'))
        ->toBe(AmlAutomationStatus::Queue->value);
});

it('returns 403 when travel AML retrigger feature flag is disabled', function (): void {
    ApplicationStorage::where('key_name', ApplicationStorageEnums::TRAVEL_AML_RETRIGGER_ENABLED)
        ->update(['value' => 0]);

    $this->postJson(RETRIGGER_TRAVEL_AML_ENDPOINT, [
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-31',
    ])
        ->assertForbidden();
});

it('includes quotes created on the boundary dates', function (): void {
    Bus::fake();

    Carbon::setTestNow('2026-01-01 00:00:00');
    TravelQuote::factory()->create([
        'quote_status_id' => QuoteStatusEnum::PolicyBooked,
        'aml_status' => AMLStatusCode::AMLPending,
    ]);

    Carbon::setTestNow('2026-01-31 23:59:59');
    TravelQuote::factory()->create([
        'quote_status_id' => QuoteStatusEnum::PolicyBooked,
        'aml_status' => AMLStatusCode::AMLPending,
    ]);
    Carbon::setTestNow(null);

    $this->postJson(RETRIGGER_TRAVEL_AML_ENDPOINT, [
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-31',
    ])
        ->assertOk()
        ->assertJson(['data' => ['dispatched_count' => 2]]);

    Bus::assertDispatched(AmlScreeningAutomationJob::class, 2);
});
