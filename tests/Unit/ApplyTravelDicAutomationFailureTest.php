<?php

declare(strict_types=1);

use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Jobs\AutomationFailedJob;
use App\Jobs\SendTravelAllianceFailedAllocationEmailJob;
use App\Models\TravelQuote;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function (): void {
    TestSchemaCreator::createMinimalSchema();
});

it('dispatches travel alliance failed allocation job for DIC failure instead of AutomationFailedJob', function (): void {
    Queue::fake();

    $userId = DB::table('users')->insertGetId([
        'name' => 'Advisor',
        'email' => 'advisor'.Str::random(8).'@example.com',
        'password' => 'secret',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $travelId = DB::table('travel_quote_request')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'code' => 'TRV-DIC-BIRD-1',
        'advisor_id' => $userId,
        'insurer_api_status_id' => null,
        'api_issuance_status_id' => null,
        'quote_status_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    TravelQuote::withoutEvents(function () use ($travelId): void {
        app(PolicyIssuanceService::class)->applyTravelDicAutomationFailure(
            TravelQuote::query()->findOrFail($travelId),
            PolicyIssuanceEnum::POLICY_DETAIL_API_FAILED_STATUS_ID,
            PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
        );
    });

    Queue::assertPushed(SendTravelAllianceFailedAllocationEmailJob::class);
    Queue::assertNotPushed(AutomationFailedJob::class);
});

it('does not dispatch travel alliance failed allocation job when insurer API was already in a failed state', function (): void {
    Queue::fake();

    $userId = DB::table('users')->insertGetId([
        'name' => 'Advisor',
        'email' => 'advisor'.Str::random(8).'@example.com',
        'password' => 'secret',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $travelId = DB::table('travel_quote_request')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'code' => 'TRV-DIC-BIRD-2',
        'advisor_id' => $userId,
        'insurer_api_status_id' => PolicyIssuanceEnum::POLICY_DETAIL_API_FAILED_STATUS_ID,
        'api_issuance_status_id' => PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID,
        'quote_status_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    TravelQuote::withoutEvents(function () use ($travelId): void {
        app(PolicyIssuanceService::class)->applyTravelDicAutomationFailure(
            TravelQuote::query()->findOrFail($travelId),
            PolicyIssuanceEnum::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID,
            PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC,
        );
    });

    Queue::assertNotPushed(SendTravelAllianceFailedAllocationEmailJob::class);
    Queue::assertNotPushed(AutomationFailedJob::class);
});

it('allocateLead never dispatches AutomationFailedJob for travel failure context because travel uses insurer-specific flows', function (): void {
    Queue::fake();

    $userId = DB::table('users')->insertGetId([
        'name' => 'Advisor',
        'email' => 'advisor'.Str::random(8).'@example.com',
        'password' => 'secret',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $travelId = DB::table('travel_quote_request')->insertGetId([
        'uuid' => (string) Str::uuid(),
        'code' => 'TRV-ALLOCATE-1',
        'advisor_id' => $userId,
        'insurer_api_status_id' => null,
        'api_issuance_status_id' => null,
        'quote_status_id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $travelQuote = TravelQuote::query()->findOrFail($travelId);

    app(PolicyIssuanceService::class)->allocateLead(
        QuoteTypes::TRAVEL->value,
        $travelQuote,
        false,
        PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED,
        PolicyIssuanceEnum::PROCESS_INVOLVED_BOOK_POLICY,
    );

    Queue::assertNotPushed(AutomationFailedJob::class);
});
