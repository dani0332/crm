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
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRenewalsSchema();
});


function makeOtherNonMotorService(): OtherNonMotorRenewalsUploadService
{
    return new OtherNonMotorRenewalsUploadService();
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
        'ref_id' => $quote->code,
        'advisor_email' => $advisor->email,
    ]);

    $service = makeOtherNonMotorService();
    $service->processSingle($lead->id, $process->id);

    $process->refresh();
    $lead->refresh();
    $quote->refresh();

    expect($process->status)->toBe(RenewalProcessStatuses::PROCESSED)
        ->and($process->fetch_plans_status)->toBe(FetchPlansStatuses::PENDING)
        ->and($process->quote_id)->toBe($quote->id)
        ->and($lead->good)->toBe(1)
        ->and($lead->cannot_upload)->toBe(0)
        ->and($quote->advisor_id)->toBe($advisor->id)
        ->and((int) $quote->assignment_type)->toBe(AssignmentTypeEnum::SYSTEM_REASSIGNED);
});

test('skips lead when advisor record is missing at processing time', function () {
    $quote = makeOtherNonMotorQuote();
    $lead = makeUploadLead();
    $process = makePendingProcess($lead, [
        'ref_id' => $quote->uuid,
        'advisor_email' => 'missing@example.com',
    ]);

    $service = makeOtherNonMotorService();
    $service->processSingle($lead->id, $process->id);

    $process->refresh();
    $lead->refresh();
    $quote->refresh();

    expect($process->status)->toBe(RenewalProcessStatuses::BAD_DATA)
        ->and($process->fetch_plans_status)->toBe(FetchPlansStatuses::OUTDATED)
        ->and($lead->cannot_upload)->toBe(1)
        ->and($lead->good)->toBe(0)
        ->and($quote->advisor_id)->toBeNull();
});

test('skips lead when ref-id resolves to a disallowed quote type', function () {
    // Business (quote_type_id=5) is not in the allowed list
    $advisor = User::factory()->create(['email' => 'advisor@example.com']);
    $quote = makeOtherNonMotorQuote(['quote_type_id' => QuoteTypes::BUSINESS->id()]);
    $lead = makeUploadLead();
    $process = makePendingProcess($lead, [
        'ref_id' => $quote->code,
        'advisor_email' => $advisor->email,
    ]);

    $service = makeOtherNonMotorService();
    $service->processSingle($lead->id, $process->id);

    $process->refresh();
    $lead->refresh();
    $quote->refresh();

    expect($process->status)->toBe(RenewalProcessStatuses::BAD_DATA)
        ->and($lead->cannot_upload)->toBe(1)
        ->and($lead->good)->toBe(0)
        ->and($quote->advisor_id)->toBeNull();
});

test('assigns advisor for corpline renewal lead', function () {
    $advisor = User::factory()->create(['email' => 'advisor@example.com']);
    $quote = makeOtherNonMotorQuote(['quote_type_id' => QuoteTypes::CORPLINE->id()]);
    $lead = makeUploadLead();
    $process = makePendingProcess($lead, [
        'ref_id' => $quote->code,
        'advisor_email' => $advisor->email,
    ]);

    $service = makeOtherNonMotorService();
    $service->processSingle($lead->id, $process->id);

    $process->refresh();
    $lead->refresh();
    $quote->refresh();

    expect($process->status)->toBe(RenewalProcessStatuses::PROCESSED)
        ->and($lead->good)->toBe(1)
        ->and($quote->advisor_id)->toBe($advisor->id);
});

test('assigns advisor for group medical renewal lead', function () {
    $advisor = User::factory()->create(['email' => 'advisor@example.com']);
    $quote = makeOtherNonMotorQuote(['quote_type_id' => QuoteTypes::GROUP_MEDICAL->id()]);
    $lead = makeUploadLead();
    $process = makePendingProcess($lead, [
        'ref_id' => $quote->code,
        'advisor_email' => $advisor->email,
    ]);

    $service = makeOtherNonMotorService();
    $service->processSingle($lead->id, $process->id);

    $process->refresh();
    $lead->refresh();
    $quote->refresh();

    expect($process->status)->toBe(RenewalProcessStatuses::PROCESSED)
        ->and($lead->good)->toBe(1)
        ->and($quote->advisor_id)->toBe($advisor->id);
});
