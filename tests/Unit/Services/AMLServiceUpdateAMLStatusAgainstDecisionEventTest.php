<?php

declare(strict_types=1);

use App\Enums\AMLDecisionStatusEnum;
use App\Enums\AMLScreeningTypeEnum;
use App\Events\AmlAutomationScreeningSucceeded;
use App\Models\PersonalQuote;
use App\Services\AMLService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Helpers\TestSchemaCreator;
use Tests\Support\Schema\SchemaUtils;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    SchemaUtils::addColumnIfMissing('kyc_logs', 'kyc_logs', function (Blueprint $table): void {
        $table->string('notes')->nullable();
        $table->string('in_adverse_media')->nullable();
        $table->string('is_owner_pep')->nullable();
        $table->string('is_controlling_pep')->nullable();
    });
    SchemaUtils::addColumnIfMissing('personal_quotes', 'aml_status', function (Blueprint $table): void {
        $table->string('aml_status')->nullable();
    });
});

/** @phpstan-return array{id: int, quote_type_id: int} */
function insertPersonalQuoteRecord(int $quoteTypeId): array
{
    $id = (int) DB::table('personal_quotes')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'code' => 'PQ-EVT-'.$quoteTypeId.'-pending',
        'quote_type_id' => $quoteTypeId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('personal_quotes')->whereKey($id)->update([
        'code' => 'PQ-EVT-'.$quoteTypeId.'-'.$id,
    ]);

    return ['id' => $id, 'quote_type_id' => $quoteTypeId];
}

it('dispatches aml automation screening succeeded for automatable lobs when screening clears', function () {
    Event::fake([AmlAutomationScreeningSucceeded::class]);

    ['id' => $quoteRequestId] = insertPersonalQuoteRecord(18); // Savings

    $kycLogId = (int) DB::table('kyc_logs')->insertGetId([
        'quote_type_id' => 18,
        'quote_request_id' => $quoteRequestId,
        'decision' => AMLDecisionStatusEnum::PASS,
        'screening_type' => AMLScreeningTypeEnum::BRIDGER,
        'screenshot' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    /** @var PersonalQuote $quote */
    $quote = PersonalQuote::query()->findOrFail($quoteRequestId);

    app(AMLService::class)->updateAMLStatusAgainstDecision([
        'aml_id' => $kycLogId,
        'aml_decision' => AMLDecisionStatusEnum::PASS,
        'notes' => '',
    ], $quote);

    Event::assertDispatched(AmlAutomationScreeningSucceeded::class, function (AmlAutomationScreeningSucceeded $event): bool {
        return $event->quoteType->value === 'Savings';
    });
});

it('does not dispatch aml automation screening succeeded for non automatable quote types when screening clears', function () {
    Event::fake([AmlAutomationScreeningSucceeded::class]);

    ['id' => $quoteRequestId] = insertPersonalQuoteRecord(1); // Car

    $kycLogId = (int) DB::table('kyc_logs')->insertGetId([
        'quote_type_id' => 1,
        'quote_request_id' => $quoteRequestId,
        'decision' => AMLDecisionStatusEnum::PASS,
        'screening_type' => AMLScreeningTypeEnum::BRIDGER,
        'screenshot' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    /** @var PersonalQuote $quote */
    $quote = PersonalQuote::query()->findOrFail($quoteRequestId);

    app(AMLService::class)->updateAMLStatusAgainstDecision([
        'aml_id' => $kycLogId,
        'aml_decision' => AMLDecisionStatusEnum::PASS,
        'notes' => '',
    ], $quote);

    Event::assertNotDispatched(AmlAutomationScreeningSucceeded::class);
});

it('does not dispatch aml automation screening succeeded when quote type id is unmapped', function () {
    Event::fake([AmlAutomationScreeningSucceeded::class]);

    ['id' => $quoteRequestId] = insertPersonalQuoteRecord(999);

    $kycLogId = (int) DB::table('kyc_logs')->insertGetId([
        'quote_type_id' => 999,
        'quote_request_id' => $quoteRequestId,
        'decision' => AMLDecisionStatusEnum::PASS,
        'screening_type' => AMLScreeningTypeEnum::BRIDGER,
        'screenshot' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    /** @var PersonalQuote $quote */
    $quote = PersonalQuote::query()->findOrFail($quoteRequestId);

    app(AMLService::class)->updateAMLStatusAgainstDecision([
        'aml_id' => $kycLogId,
        'aml_decision' => AMLDecisionStatusEnum::PASS,
        'notes' => '',
    ], $quote);

    Event::assertNotDispatched(AmlAutomationScreeningSucceeded::class);
});
