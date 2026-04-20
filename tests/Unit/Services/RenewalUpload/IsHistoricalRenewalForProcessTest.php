<?php

declare(strict_types=1);

use App\Enums\FetchPlansStatuses;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Models\InsuranceProvider;
use App\Models\InsuranceProviderTransition;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use App\Services\RenewalsUploadService;
use Tests\Helpers\TestSchemaCreator;

/**
 * Regression tests for RenewalsUploadService::isHistoricalRenewalForProcess().
 *
 * Protects the quote_type scoping on the historical-renewal exists() query.
 * Without the quote_type filter, a numeric quote_id collision across LOBs
 * (CarQuote id=42 vs HealthQuote id=42) would produce a false positive.
 */
beforeEach(function () {
    TestSchemaCreator::createRenewalsSchema();
});

if (! function_exists('createHistoricalRenewalService')) {
    function createHistoricalRenewalService(): RenewalsUploadService
    {
        return (new ReflectionClass(RenewalsUploadService::class))->newInstanceWithoutConstructor();
    }
}

if (! function_exists('createHistoricalRenewalLead')) {
    function createHistoricalRenewalLead(): RenewalsUploadLeads
    {
        return RenewalsUploadLeads::create([
            'file_name' => 'test.xlsx',
            'file_path' => 'test/path.xlsx',
            'quote_type' => 'CAR',
            'status' => 'IN_PROGRESS',
            'renewal_import_type' => RenewalsUploadType::UPDATE_LEADS,
            'is_sic' => 0,
        ]);
    }
}

if (! function_exists('createActiveCarTransition')) {
    function createActiveCarTransition(): InsuranceProviderTransition
    {
        $source = InsuranceProvider::create(['code' => 'RSA', 'text' => 'RSA']);
        $target = InsuranceProvider::create(['code' => 'AXA', 'text' => 'GIG AXA']);

        return InsuranceProviderTransition::create([
            'source_insurance_provider_id' => $source->id,
            'target_insurance_provider_id' => $target->id,
            'is_active' => true,
        ]);
    }
}

test('returns false when the given process is not a transitionable lead', function () {
    $lead = createHistoricalRenewalLead();

    $current = RenewalQuoteProcess::create([
        'renewals_upload_lead_id' => $lead->id,
        'quote_id' => 42,
        'quote_type' => 'CAR',
        'status' => RenewalProcessStatuses::PLANS_FETCHED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::FETCHED,
        'email_sent' => true,
        'data' => [],
    ]);

    RenewalQuoteProcess::create([
        'renewals_upload_lead_id' => $lead->id,
        'quote_id' => 42,
        'quote_type' => 'CAR',
        'status' => RenewalProcessStatuses::PLANS_FETCHED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::FETCHED,
        'email_sent' => true,
        'data' => [],
    ]);

    $result = createHistoricalRenewalService()->isHistoricalRenewalForProcess($current);

    expect($result)->toBeFalse();
});

test('returns true when a prior matching CAR process exists and lead is transitionable', function () {
    $lead = createHistoricalRenewalLead();
    $transition = createActiveCarTransition();

    $current = RenewalQuoteProcess::create([
        'renewals_upload_lead_id' => $lead->id,
        'quote_id' => 42,
        'quote_type' => 'CAR',
        'status' => RenewalProcessStatuses::PLANS_FETCHED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::FETCHED,
        'email_sent' => true,
        'insurance_provider_transition_id' => $transition->id,
        'data' => [],
    ]);

    RenewalQuoteProcess::create([
        'renewals_upload_lead_id' => $lead->id,
        'quote_id' => 42,
        'quote_type' => 'CAR',
        'status' => RenewalProcessStatuses::PLANS_FETCHED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::FETCHED,
        'email_sent' => true,
        'data' => [],
    ]);

    $result = createHistoricalRenewalService()->isHistoricalRenewalForProcess($current);

    expect($result)->toBeTrue();
});

test('does not count renewal processes from a different LOB with same numeric quote_id', function () {
    $lead = createHistoricalRenewalLead();
    $transition = createActiveCarTransition();

    $current = RenewalQuoteProcess::create([
        'renewals_upload_lead_id' => $lead->id,
        'quote_id' => 42,
        'quote_type' => 'CAR',
        'status' => RenewalProcessStatuses::PLANS_FETCHED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::FETCHED,
        'email_sent' => true,
        'insurance_provider_transition_id' => $transition->id,
        'data' => [],
    ]);

    // Same numeric quote_id, different LOB. Must NOT be counted as historical.
    RenewalQuoteProcess::create([
        'renewals_upload_lead_id' => $lead->id,
        'quote_id' => 42,
        'quote_type' => 'HEA',
        'status' => RenewalProcessStatuses::PLANS_FETCHED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::FETCHED,
        'email_sent' => true,
        'data' => [],
    ]);

    RenewalQuoteProcess::create([
        'renewals_upload_lead_id' => $lead->id,
        'quote_id' => 42,
        'quote_type' => 'TRA',
        'status' => RenewalProcessStatuses::PLANS_FETCHED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::FETCHED,
        'email_sent' => true,
        'data' => [],
    ]);

    $result = createHistoricalRenewalService()->isHistoricalRenewalForProcess($current);

    expect($result)->toBeFalse();
});

test('excludes the current process from the historical exists check', function () {
    $lead = createHistoricalRenewalLead();
    $transition = createActiveCarTransition();

    $current = RenewalQuoteProcess::create([
        'renewals_upload_lead_id' => $lead->id,
        'quote_id' => 42,
        'quote_type' => 'CAR',
        'status' => RenewalProcessStatuses::PLANS_FETCHED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::FETCHED,
        'email_sent' => true,
        'insurance_provider_transition_id' => $transition->id,
        'data' => [],
    ]);

    // No other matching processes exist.
    $result = createHistoricalRenewalService()->isHistoricalRenewalForProcess($current);

    expect($result)->toBeFalse();
});
