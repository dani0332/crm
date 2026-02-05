<?php

use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Models\PersonalQuote;
use App\Models\PolicyIssuance;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createCyberSchema();
});

it('persists provided insurer and api issuance statuses', function () {
    $quote = PersonalQuote::factory()
        ->cyberQuote()
        ->withCyberDependencies()
        ->create(['quote_status_id' => QuoteStatusEnum::POLICY_BOOKING_FAILED]);

    $process = PolicyIssuance::withoutEvents(fn () => createAwnicPolicyIssuanceProcess($quote));
    PolicyIssuance::withoutEvents(fn () => $process->update(['status' => PolicyIssuanceEnum::COMPLETED_STATUS]));

    app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus(
        $quote->fresh(),
        QuoteTypes::CYBER->value,
        PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID,
        PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID,
        PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY
    );

    $quote->refresh();

    expect($quote->insurer_api_status_id)->toBe(PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID)
        ->and($quote->api_issuance_status_id)->toBe(PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);
});

it('derives api issuance status when automation completes successfully', function () {
    $quote = PersonalQuote::factory()
        ->cyberQuote()
        ->withCyberDependencies()
        ->create(['quote_status_id' => QuoteStatusEnum::PolicyBooked]);

    $process = PolicyIssuance::withoutEvents(fn () => createAwnicPolicyIssuanceProcess($quote));
    PolicyIssuance::withoutEvents(fn () => $process->update(['status' => PolicyIssuanceEnum::COMPLETED_STATUS]));

    app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus(
        $quote->fresh(),
        QuoteTypes::CYBER->value
    );

    $quote->refresh();

    expect($quote->api_issuance_status_id)->toBe(PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID)
        ->and($quote->insurer_api_status_id)->toBeNull();
});

