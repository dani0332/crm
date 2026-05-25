<?php

declare(strict_types=1);

use App\Enums\ProcessStatusCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Models\PersonalQuote;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use App\Services\CQF\NonMotor\NonMotorCQFRegistry;
use App\Services\CQF\NonMotor\NonMotorCQFRenewalExecutionService;
use App\Services\CQF\NonMotor\Pipes\DuplicateCheckPipe;
use App\Services\CQF\NonMotor\Pipes\ForeignKeyValidationPipe;
use App\Services\CQF\NonMotor\Pipes\InslyCheckPipe;
use App\Services\CQF\NonMotor\Pipes\LOBValidationPipe;
use App\Services\CQF\NonMotor\Pipes\StoragePipe;
use App\Services\RenewalsUploadService;
use Illuminate\Pipeline\Pipeline;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRenewalsSchema();

    $renewalsUploadService = Mockery::mock(RenewalsUploadService::class);
    $renewalsUploadService->shouldReceive('generateRandomString')->andReturn('TESTCODE');

    $this->service = new NonMotorCQFRenewalExecutionService(
        registry: Mockery::mock(NonMotorCQFRegistry::class)->shouldIgnoreMissing(),
        lobValidationPipe: Mockery::mock(LOBValidationPipe::class)->shouldIgnoreMissing(),
        duplicateCheckPipe: Mockery::mock(DuplicateCheckPipe::class)->shouldIgnoreMissing(),
        inslyCheckPipe: Mockery::mock(InslyCheckPipe::class)->shouldIgnoreMissing(),
        foreignKeyValidationPipe: Mockery::mock(ForeignKeyValidationPipe::class)->shouldIgnoreMissing(),
        storagePipe: Mockery::mock(StoragePipe::class)->shouldIgnoreMissing(),
        renewalsUploadService: $renewalsUploadService,
        pipeline: Mockery::mock(Pipeline::class)->shouldIgnoreMissing(),
    );
});

test('creates renewals upload leads with correct data for a given LOB', function () {
    $lead = $this->service->createRenewalUploadLeadsForLOB(QuoteTypes::BIKE, 150);

    expect($lead)->toBeInstanceOf(RenewalsUploadLeads::class)
        ->and($lead->quote_type)->toBe('BIK')
        ->and($lead->renewal_import_code)->toBe('TESTCODE')
        ->and($lead->renewal_import_type)->toBe(RenewalsUploadType::CREATE_LEADS)
        ->and($lead->status)->toBe(ProcessStatusCode::UPLOADED)
        ->and((int) $lead->total_records)->toBe(150)
        ->and((int) $lead->good)->toBe(0)
        ->and((int) $lead->cannot_upload)->toBe(0);
});

test('marks quote as completed and increments good count', function () {
    $lead = RenewalsUploadLeads::factory()->create(['good' => 0]);

    $quote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $quote->shouldReceive('getAttribute')->with('id')->andReturn(5);
    $quote->shouldReceive('getAttribute')->with('policy_number')->andReturn('POL-001');
    $quote->shouldReceive('toArray')->andReturn(['id' => 5, 'policy_number' => 'POL-001']);

    $markCompleted = Closure::bind(
        fn ($q, $l) => $this->markQuoteAsCompleted($q, $l),
        $this->service,
        NonMotorCQFRenewalExecutionService::class
    );

    $markCompleted($quote, $lead);

    $lead->refresh();
    expect((int) $lead->good)->toBe(1);

    $process = RenewalQuoteProcess::where('renewals_upload_lead_id', $lead->id)->first();
    expect($process)->not->toBeNull()
        ->and($process->status)->toBe(RenewalProcessStatuses::PROCESSED)
        ->and($process->type)->toBe(RenewalsUploadType::CREATE_LEADS);
});

test('marks quote as failed and increments cannot_upload count', function () {
    $lead = RenewalsUploadLeads::factory()->create(['cannot_upload' => 0]);

    $quote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $quote->shouldReceive('getAttribute')->with('id')->andReturn(6);
    $quote->shouldReceive('getAttribute')->with('policy_number')->andReturn('POL-002');
    $quote->shouldReceive('toArray')->andReturn(['id' => 6, 'policy_number' => 'POL-002']);

    $markFailed = Closure::bind(
        fn ($q, $l, $errors, $mapper) => $this->markQuoteAsFailed($q, $l, $errors, $mapper),
        $this->service,
        NonMotorCQFRenewalExecutionService::class
    );

    $markFailed($quote, $lead, ['duplicate' => 'Quote already exists'], null);

    $lead->refresh();
    expect((int) $lead->cannot_upload)->toBe(1);

    $process = RenewalQuoteProcess::where('renewals_upload_lead_id', $lead->id)->first();
    expect($process)->not->toBeNull()
        ->and($process->status)->toBe(RenewalProcessStatuses::BAD_DATA)
        ->and($process->type)->toBe(RenewalsUploadType::CREATE_LEADS);
});

test('processQuoteForJob increments good and records PROCESSED status on pipeline success', function () {
    $lead = RenewalsUploadLeads::factory()->create(['good' => 0]);
    $quote = PersonalQuote::factory()->create(['quote_type_id' => QuoteTypeId::Cycle]);

    $newQuote = Mockery::mock(PersonalQuote::class)->shouldIgnoreMissing();
    $passThrough = fn ($ctx, $next) => $next($ctx);

    $service = new class(registry: Mockery::mock(NonMotorCQFRegistry::class)->shouldIgnoreMissing(), lobValidationPipe: tap(Mockery::mock(LOBValidationPipe::class), fn ($m) => $m->shouldReceive('handle')->andReturnUsing($passThrough)), duplicateCheckPipe: tap(Mockery::mock(DuplicateCheckPipe::class), fn ($m) => $m->shouldReceive('handle')->andReturnUsing($passThrough)), inslyCheckPipe: tap(Mockery::mock(InslyCheckPipe::class), fn ($m) => $m->shouldReceive('handle')->andReturnUsing($passThrough)), foreignKeyValidationPipe: tap(Mockery::mock(ForeignKeyValidationPipe::class), fn ($m) => $m->shouldReceive('handle')->andReturnUsing($passThrough)), storagePipe: tap(Mockery::mock(StoragePipe::class), fn ($m) => $m->shouldReceive('handle')->andReturnUsing(function ($ctx, $next) use ($newQuote) {
        $ctx->newQuote = $newQuote;

        return $next($ctx);
    })),
        renewalsUploadService: Mockery::mock(RenewalsUploadService::class)->shouldIgnoreMissing(),
        pipeline: app(Pipeline::class),
    ) extends NonMotorCQFRenewalExecutionService {
        protected function getEagerLoadRelationsForLOB(QuoteTypes $quoteType, string $source = ''): array
        {
            return [];
        }
    };

    $service->processQuoteForJob($quote->id, QuoteTypes::PERSONAL->value, QuoteTypes::CYCLE, $lead->id, 120);

    $lead->refresh();
    expect((int) $lead->good)->toBe(1);

    $process = RenewalQuoteProcess::where('renewals_upload_lead_id', $lead->id)->first();
    expect($process)->not->toBeNull()
        ->and($process->status)->toBe(RenewalProcessStatuses::PROCESSED);
});

afterEach(function () {
    Mockery::close();
});
