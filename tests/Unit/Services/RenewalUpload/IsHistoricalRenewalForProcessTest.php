<?php

declare(strict_types=1);

use App\Enums\FetchPlansStatuses;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Models\CarPlan;
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

if (! function_exists('createTransitionCarPlan')) {
    function createTransitionCarPlan(InsuranceProviderTransition $transition): CarPlan
    {
        return CarPlan::create([
            'provider_id' => $transition->targetProvider->id,
            'text' => 'Transition Plan',
            'repair_type' => 'TPL',
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
    $plan = createTransitionCarPlan($transition);

    $current = RenewalQuoteProcess::create([
        'renewals_upload_lead_id' => $lead->id,
        'quote_id' => 42,
        'quote_type' => 'CAR',
        'status' => RenewalProcessStatuses::PLANS_FETCHED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::FETCHED,
        'email_sent' => true,
        'insurance_provider_transition_id' => $transition->id,
        'data' => [
            'insurer' => $transition->sourceProvider->code,
            'provider_name' => $transition->targetProvider->text,
            'plan_name' => $plan->text,
            'plan_type' => $plan->repair_type,
        ],
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

test('returns true for legacy records without transition_id when current data still matches an active transition and plan', function () {
    $lead = createHistoricalRenewalLead();
    $transition = createActiveCarTransition();
    $plan = createTransitionCarPlan($transition);

    $current = RenewalQuoteProcess::create([
        'renewals_upload_lead_id' => $lead->id,
        'quote_id' => 42,
        'quote_type' => 'CAR',
        'status' => RenewalProcessStatuses::PLANS_FETCHED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::FETCHED,
        'email_sent' => true,
        'insurance_provider_transition_id' => null,
        'data' => [
            'insurer' => $transition->sourceProvider->code,
            'provider_name' => $transition->targetProvider->text,
            'plan_name' => $plan->text,
            'plan_type' => $plan->repair_type,
        ],
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
        'data' => [
            'insurer' => $transition->sourceProvider->code,
            'provider_name' => $transition->targetProvider->text,
        ],
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
        'data' => [
            'insurer' => $transition->sourceProvider->code,
            'provider_name' => $transition->targetProvider->text,
        ],
    ]);

    // No other matching processes exist.
    $result = createHistoricalRenewalService()->isHistoricalRenewalForProcess($current);

    expect($result)->toBeFalse();
});

test('returns false when provider_name was cleared after validation persisted transition_id', function () {
    // Regression: the stored insurance_provider_transition_id is set during
    // validation from the then-current lead data. If provider_name is later
    // cleared, isHistoricalRenewalForProcess must re-check the transition
    // against the current lead data rather than trusting the persisted id,
    // otherwise $isRenewalHistorical returns true for quotes whose data no
    // longer qualifies as transitionable and skews plan sorting/OCB routing.
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
        'data' => [
            'insurer' => $transition->sourceProvider->code,
            'provider_name' => null,
        ],
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

test('returns false when provider_name was swapped to a different provider after validation', function () {
    $lead = createHistoricalRenewalLead();
    $transition = createActiveCarTransition();

    // A different provider the lead was swapped to — no active transition to this one.
    InsuranceProvider::create(['code' => 'GIG', 'text' => 'Different Provider']);

    $current = RenewalQuoteProcess::create([
        'renewals_upload_lead_id' => $lead->id,
        'quote_id' => 42,
        'quote_type' => 'CAR',
        'status' => RenewalProcessStatuses::PLANS_FETCHED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::FETCHED,
        'email_sent' => true,
        'insurance_provider_transition_id' => $transition->id,
        'data' => [
            'insurer' => $transition->sourceProvider->code,
            'provider_name' => 'Different Provider',
        ],
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

test('returns false when insurer code was changed after validation', function () {
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
        'data' => [
            'insurer' => 'TM',
            'provider_name' => $transition->targetProvider->text,
        ],
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

test('isHistoricalRenewalForProcess respects precomputed isTransitionableLead false without treating as historical', function () {
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
        'data' => [
            'insurer' => $transition->sourceProvider->code,
            'provider_name' => $transition->targetProvider->text,
        ],
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

    $result = createHistoricalRenewalService()->isHistoricalRenewalForProcess($current, false);

    expect($result)->toBeFalse();
});

test('isHistoricalRenewalForProcess with precomputed isTransitionableLead true still applies data consistency guard', function () {
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
        'data' => [
            'insurer' => $transition->sourceProvider->code,
            'provider_name' => null,
        ],
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

    $result = createHistoricalRenewalService()->isHistoricalRenewalForProcess($current, true);

    expect($result)->toBeFalse();
});
