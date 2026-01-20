<?php

declare(strict_types=1);

use App\Enums\AssignmentTypeEnum;
use App\Enums\FetchPlansStatuses;
use App\Enums\LeadSourceEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\QuoteTypes;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Models\PersonalQuote;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use App\Models\User;
use App\Services\OtherNonMotorRenewalsUploadService;
use App\Services\QuoteDocumentService;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRenewalsSchema();
});

afterEach(function () {
    Mockery::close();
});

function makeOtherNonMotorService(): OtherNonMotorRenewalsUploadService
{
    return new OtherNonMotorRenewalsUploadService(\Mockery::mock(QuoteDocumentService::class));
}

function callValidateAndAssignLead(OtherNonMotorRenewalsUploadService $service, RenewalQuoteProcess $process, RenewalsUploadLeads $lead): void
{
    $method = new \ReflectionMethod($service, 'validateAndAssignLead');
    $method->setAccessible(true);
    $method->invoke($service, $process, $lead);
}

function makeOtherNonMotorQuote(array $overrides = []): PersonalQuote
{
    $state = [
        'quote_type_id' => QuoteTypes::PET->id(),
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
    ];

    return PersonalQuote::factory()
        ->forQuoteType($state['quote_type_id'])
        ->state($state)
        ->create($overrides);
}

function makeUploadLead(): RenewalsUploadLeads
{
    return RenewalsUploadLeads::factory()
        ->forQuoteType(OtherNonMotorRenewalsUploadService::QUOTE_TYPE)
        ->state([
            'status' => ProcessStatusCode::IN_PROGRESS,
            'renewal_import_type' => RenewalsUploadType::UPDATE_LEADS,
            'is_sic' => 0,
            'good' => 0,
            'cannot_upload' => 0,
        ])->create();
}

function makePendingProcess(RenewalsUploadLeads $lead, array $data): RenewalQuoteProcess
{
    return RenewalQuoteProcess::factory()
        ->forLead($lead->id)
        ->withQuoteType(OtherNonMotorRenewalsUploadService::QUOTE_TYPE)
        ->withData($data)
        ->create([
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'status' => RenewalProcessStatuses::NEW,
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
        ]);
}

test('assigns advisor for eligible other non-motor renewal lead', function () {
    $advisor = User::factory()->create(['email' => 'advisor@example.com']);
    $quote = makeOtherNonMotorQuote();
    $lead = makeUploadLead();
    $process = makePendingProcess($lead, [
        'ref_id' => $quote->uuid,
        'advisor_email' => $advisor->email,
    ]);

    $service = makeOtherNonMotorService();
    callValidateAndAssignLead($service, $process, $lead);

    $process->refresh();
    $lead->refresh();
    $quote->refresh();

    expect($process->status)->toBe(RenewalProcessStatuses::PROCESSED)
        ->and($process->fetch_plans_status)->toBe(FetchPlansStatuses::FETCHED)
        ->and($process->quote_id)->toBe($quote->id)
        ->and($lead->good)->toBe(1)
        ->and($lead->cannot_upload)->toBe(0)
        ->and($quote->advisor_id)->toBe($advisor->id)
        ->and((int) $quote->assignment_type)->toBe(AssignmentTypeEnum::SYSTEM_REASSIGNED);
});

test('skips lead when source is not renewal_upload', function () {
    $advisor = User::factory()->create(['email' => 'advisor@example.com']);
    $quote = makeOtherNonMotorQuote(['source' => 'WEB']);
    $lead = makeUploadLead();
    $process = makePendingProcess($lead, [
        'ref_id' => $quote->uuid,
        'advisor_email' => $advisor->email,
    ]);

    $service = makeOtherNonMotorService();
    callValidateAndAssignLead($service, $process, $lead);

    $process->refresh();
    $lead->refresh();
    $quote->refresh();

    expect($process->status)->toBe(RenewalProcessStatuses::BAD_DATA)
        ->and($process->fetch_plans_status)->toBe(FetchPlansStatuses::OUTDATED)
        ->and($lead->cannot_upload)->toBe(1)
        ->and($lead->good)->toBe(0)
        ->and($quote->advisor_id)->toBeNull();
});

test('skips manually assigned leads', function () {
    $advisor = User::factory()->create(['email' => 'advisor@example.com']);
    $quote = makeOtherNonMotorQuote([
        'assignment_type' => AssignmentTypeEnum::MANUAL_ASSIGNED,
    ]);
    $lead = makeUploadLead();
    $process = makePendingProcess($lead, [
        'ref_id' => $quote->uuid,
        'advisor_email' => $advisor->email,
    ]);

    $service = makeOtherNonMotorService();
    callValidateAndAssignLead($service, $process, $lead);

    $process->refresh();
    $lead->refresh();
    $quote->refresh();

    expect($process->status)->toBe(RenewalProcessStatuses::BAD_DATA)
        ->and($lead->cannot_upload)->toBe(1)
        ->and($quote->advisor_id)->toBeNull();
});
