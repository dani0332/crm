<?php

declare(strict_types=1);

use App\Enums\FetchPlansStatuses;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\QuoteTypeShortCode;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Models\InsuranceProvider;
use App\Models\InsuranceProviderTransition;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use App\Services\CarQuoteService;
use App\Services\RenewalsUploadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Helpers\TestSchemaCreator;

/**
 * Regression: {@see CarQuoteService::getOcbDetails()} must expose
 * {@code $carQuote->isTransitionableLead} using the fresh
 * {@code isTransitionableLeadWithCurrentData()} check, not the
 * stored-only {@code RenewalQuoteProcess::checkIsTransitionableLead()}.
 *
 * Without this guard, a lead whose {@code provider_name}/{@code insurer}
 * is mutated after validation (stale {@code insurance_provider_transition_id})
 * would incorrectly be reported as transitionable to API consumers,
 * matching the bug the CRUDController/OCB-email paths were refactored to fix.
 */
beforeEach(function () {
    if (! extension_loaded('pdo_sqlite')) {
        test()->markTestSkipped('PDO SQLite driver required for this test.');
    }

    TestSchemaCreator::createRenewalsSchema();

    $renewalsUploadService = (new ReflectionClass(RenewalsUploadService::class))->newInstanceWithoutConstructor();
    app()->instance(RenewalsUploadService::class, $renewalsUploadService);
});

afterEach(function () {
    Mockery::close();
});

if (! function_exists('createGetOcbDetailsServiceStub')) {
    /**
     * CarQuoteService with constructor bypassed and {@code getPlans()} stubbed
     * to return an empty array so the KEN plan fetch and PDF export paths
     * are skipped. The method under test (getOcbDetails) still sets
     * {@code isTransitionableLead} on the returned CarQuote regardless of
     * plan count.
     */
    function createGetOcbDetailsServiceStub(): CarQuoteService
    {
        return new class extends CarQuoteService
        {
            public function __construct() {}

            public function getPlans($id, $isRenewalSort = false, $isDisabledEnabled = false, $useKen2Endpoint = false, $isRenewalHistorical = false)
            {
                return [];
            }
        };
    }
}

if (! function_exists('createGetOcbDetailsCarQuote')) {
    function createGetOcbDetailsCarQuote(string $uuid): int
    {
        return (int) DB::table('car_quote_request')->insertGetId([
            'uuid' => $uuid,
            'code' => 'CAR-'.substr($uuid, 0, 8),
            'first_name' => 'Test',
            'last_name' => 'Lead',
            'email' => 'test@example.com',
            'is_ecommerce' => 0,
            'is_modified' => false,
            'policy_issuance_automation_enabled' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

test('getOcbDetails returns isTransitionableLead=false when stored transition is stale (provider_name mutated post-validation)', function () {
    $source = InsuranceProvider::create(['code' => InsuranceProvidersEnum::RSA, 'text' => 'RSA']);
    $target = InsuranceProvider::create(['code' => InsuranceProvidersEnum::AXA, 'text' => 'GIG AXA']);

    $transition = InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $source->id,
        'target_insurance_provider_id' => $target->id,
        'is_active' => true,
    ]);

    $uploadLead = RenewalsUploadLeads::create([
        'file_name' => 'test.xlsx',
        'file_path' => 'test/path.xlsx',
        'quote_type' => QuoteTypeShortCode::CAR,
        'status' => 'IN_PROGRESS',
        'renewal_import_type' => RenewalsUploadType::UPDATE_LEADS,
        'is_sic' => 0,
    ]);

    $uuid = (string) Str::uuid();
    $quoteId = createGetOcbDetailsCarQuote($uuid);

    RenewalQuoteProcess::create([
        'renewals_upload_lead_id' => $uploadLead->id,
        'quote_id' => $quoteId,
        'quote_type' => QuoteTypeShortCode::CAR,
        'status' => RenewalProcessStatuses::PLANS_FETCHED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::FETCHED,
        'email_sent' => true,
        'insurance_provider_transition_id' => $transition->id,
        'data' => [
            'insurer' => InsuranceProvidersEnum::RSA,
            'provider_name' => '',
        ],
    ]);

    $service = createGetOcbDetailsServiceStub();

    $result = $service->getOcbDetails($uuid);

    expect($result)->not->toBeNull()
        ->and($result->latestUpdateRenewalQuoteProcess)->not->toBeNull()
        ->and($result->latestUpdateRenewalQuoteProcess->checkIsTransitionableLead())->toBeTrue()
        ->and($result->isTransitionableLead)->toBeFalse();
});

test('getOcbDetails returns isTransitionableLead=true when stored transition matches current lead data', function () {
    $source = InsuranceProvider::create(['code' => InsuranceProvidersEnum::RSA, 'text' => 'RSA']);
    $target = InsuranceProvider::create(['code' => InsuranceProvidersEnum::AXA, 'text' => 'GIG AXA']);

    $transition = InsuranceProviderTransition::create([
        'source_insurance_provider_id' => $source->id,
        'target_insurance_provider_id' => $target->id,
        'is_active' => true,
    ]);

    $uploadLead = RenewalsUploadLeads::create([
        'file_name' => 'test.xlsx',
        'file_path' => 'test/path.xlsx',
        'quote_type' => QuoteTypeShortCode::CAR,
        'status' => 'IN_PROGRESS',
        'renewal_import_type' => RenewalsUploadType::UPDATE_LEADS,
        'is_sic' => 0,
    ]);

    $uuid = (string) Str::uuid();
    $quoteId = createGetOcbDetailsCarQuote($uuid);

    RenewalQuoteProcess::create([
        'renewals_upload_lead_id' => $uploadLead->id,
        'quote_id' => $quoteId,
        'quote_type' => QuoteTypeShortCode::CAR,
        'status' => RenewalProcessStatuses::PLANS_FETCHED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::FETCHED,
        'email_sent' => true,
        'insurance_provider_transition_id' => $transition->id,
        'data' => [
            'insurer' => InsuranceProvidersEnum::RSA,
            'provider_name' => 'GIG AXA',
        ],
    ]);

    $service = createGetOcbDetailsServiceStub();

    $result = $service->getOcbDetails($uuid);

    expect($result)->not->toBeNull()
        ->and($result->isTransitionableLead)->toBeTrue();
});
