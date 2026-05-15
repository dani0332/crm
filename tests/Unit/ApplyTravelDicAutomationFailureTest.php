<?php

declare(strict_types=1);

use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Jobs\AutomationFailedJob;
use App\Jobs\SendTravelAllianceFailedAllocationEmailJob;
use App\Models\TravelQuote;
use App\Models\User;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Illuminate\Support\Facades\Queue;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function (): void {
    TestSchemaCreator::createMinimalSchema();
});

it('dispatches travel alliance failed allocation job for DIC failure instead of AutomationFailedJob', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $travelQuote = TravelQuote::factory()->create([
        'code' => 'TRV-DIC-BIRD-1',
        'advisor_id' => $user->id,
        'insurer_api_status_id' => null,
        'api_issuance_status_id' => null,
        'quote_status_id' => 1,
    ]);

    TravelQuote::withoutEvents(function () use ($travelQuote): void {
        app(PolicyIssuanceService::class)->applyTravelDicAutomationResult(
            TravelQuote::query()->findOrFail($travelQuote->id),
            false,
            PolicyIssuanceEnum::POLICY_DETAIL_API_FAILED_STATUS_ID,
            PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
        );
    });

    Queue::assertPushed(SendTravelAllianceFailedAllocationEmailJob::class);
    Queue::assertNotPushed(AutomationFailedJob::class);
});

it('does not dispatch travel alliance failed allocation job when insurer API was already in a failed state', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $travelQuote = TravelQuote::factory()->create([
        'code' => 'TRV-DIC-BIRD-2',
        'advisor_id' => $user->id,
        'insurer_api_status_id' => PolicyIssuanceEnum::POLICY_DETAIL_API_FAILED_STATUS_ID,
        'api_issuance_status_id' => PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID,
        'quote_status_id' => 1,
    ]);

    TravelQuote::withoutEvents(function () use ($travelQuote): void {
        app(PolicyIssuanceService::class)->applyTravelDicAutomationResult(
            TravelQuote::query()->findOrFail($travelQuote->id),
            false,
            PolicyIssuanceEnum::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID,
            PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC,
        );
    });

    Queue::assertNotPushed(SendTravelAllianceFailedAllocationEmailJob::class);
    Queue::assertNotPushed(AutomationFailedJob::class);
});

it('sets api_issuance_status to YES and does not write insurer_api_status or notify on success', function (): void {
    Queue::fake();

    $travelQuote = TravelQuote::factory()->create([
        'code' => 'TRV-DIC-SUCCESS-1',
        'insurer_api_status_id' => null,
        'api_issuance_status_id' => null,
        'quote_status_id' => 1,
    ]);

    TravelQuote::withoutEvents(function () use ($travelQuote): void {
        app(PolicyIssuanceService::class)->applyTravelDicAutomationResult(
            TravelQuote::query()->findOrFail($travelQuote->id),
            true,
        );
    });

    $fresh = $travelQuote->fresh();
    expect($fresh->api_issuance_status_id)->toBe(PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID)
        ->and($fresh->insurer_api_status_id)->toBeNull();

    Queue::assertNotPushed(SendTravelAllianceFailedAllocationEmailJob::class);
    Queue::assertNotPushed(AutomationFailedJob::class);
});

it('allocateLead never dispatches AutomationFailedJob for travel failure context because travel uses insurer-specific flows', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $travelQuote = TravelQuote::factory()->create([
        'code' => 'TRV-ALLOCATE-1',
        'advisor_id' => $user->id,
        'insurer_api_status_id' => null,
        'api_issuance_status_id' => null,
        'quote_status_id' => 1,
    ]);

    app(PolicyIssuanceService::class)->allocateLead(
        QuoteTypes::TRAVEL->value,
        $travelQuote,
        false,
        PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED,
        PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY,
    );

    Queue::assertNotPushed(AutomationFailedJob::class);
});
