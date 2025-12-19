<?php

declare(strict_types=1);

use App\Enums\ApplicationStorageEnums;
use App\Enums\InsuranceProviderEnum;
use App\Enums\NgiEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Models\InsuranceProvider;
use App\Models\PersonalQuote;
use App\Models\PolicyIssuance;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiInsuranceService;
use Illuminate\Support\Facades\Cache;
use Tests\Helpers\NgiPolicyIssuanceMockHelper;
use Tests\Helpers\NgiPolicyIssuanceTestDataBuilder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createDeviceSchema();
    enableNgiDeviceAutomation();
});

afterEach(function () {
    Mockery::close();
    Cache::flush();
});

describe('NgiInsuranceService Policy Issuance', function () {
    test('can create policy issuance schedule for Device quote', function () {
        $quote = createDeviceQuoteWithDependencies();
        $insurer = getNgiInsuranceProvider();

        $ngiService = app(NgiInsuranceService::class);
        $policyIssuance = $ngiService->createPolicyIssuanceSchedule($quote, $insurer);

        // Verify policy issuance was created
        $createdIssuance = PolicyIssuance::where('model_type', PersonalQuote::class)
            ->where('model_id', $quote->id)
            ->first();

        expect($createdIssuance)->not->toBeNull()
            ->and($createdIssuance->insurance_provider_id)->toBe($insurer->id)
            ->and($createdIssuance->quote_type)->toBe(QuoteTypes::DEVICE->value)
            ->and($createdIssuance->status)->toBe(PolicyIssuanceEnum::PENDING_STATUS);
    });

    test('does not create policy issuance schedule when automation is disabled', function () {
        disableNgiDeviceAutomation();

        $quote = createDeviceQuoteWithDependencies();
        $insurer = getNgiInsuranceProvider();

        $ngiService = app(NgiInsuranceService::class);
        $ngiService->createPolicyIssuanceSchedule($quote, $insurer);

        // Verify no policy issuance was created
        $createdIssuance = PolicyIssuance::where('model_type', PersonalQuote::class)
            ->where('model_id', $quote->id)
            ->first();

        expect($createdIssuance)->toBeNull();
    });

    test('validates required data before policy issuance execution', function () {
        $quote = createDeviceQuoteWithDependencies();
        $process = createDevicePolicyIssuanceProcess($quote);

        $ngiService = app(NgiInsuranceService::class);
        $response = $ngiService->executeSteps($process);

        // Should have status key in response
        expect($response)->toHaveKey('status');
    });

    test('validates required data returns error when customer is missing', function () {
        $quote = createDeviceQuoteWithoutCustomer();
        $process = createDevicePolicyIssuanceProcess($quote);

        $ngiService = app(NgiInsuranceService::class);
        $response = $ngiService->executeSteps($process);

        expect($response['status'])->toBeFalse()
            ->and($response['error'])->toContain('customer');
    });

    test('validates required data returns error when device quote is missing', function () {
        $quote = createDeviceQuoteWithoutDeviceDetails();
        $process = createDevicePolicyIssuanceProcess($quote);

        $ngiService = app(NgiInsuranceService::class);
        $response = $ngiService->executeSteps($process);

        expect($response['status'])->toBeFalse()
            ->and($response['error'])->toContain('device quote');
    });

    test('validates required data returns error when IMEI is missing', function () {
        $quote = createDeviceQuoteWithoutImei();
        $process = createDevicePolicyIssuanceProcess($quote);

        $ngiService = app(NgiInsuranceService::class);
        $response = $ngiService->executeSteps($process);

        expect($response['status'])->toBeFalse()
            ->and($response['error'])->toContain('IMEI');
    });

    test('validates required data returns error when insurer quote number is missing', function () {
        $quote = createDeviceQuoteWithoutInsurerQuoteNumber();
        $process = createDevicePolicyIssuanceProcess($quote);

        $ngiService = app(NgiInsuranceService::class);
        $response = $ngiService->executeSteps($process);

        expect($response['status'])->toBeFalse()
            ->and($response['error'])->toContain('insurer quote number');
    });

    test('validates required data returns error when emirates id is missing', function () {
        $quote = createDeviceQuoteWithCustomerMissingEmiratesId();
        $process = createDevicePolicyIssuanceProcess($quote);

        $ngiService = app(NgiInsuranceService::class);
        $response = $ngiService->executeSteps($process);

        expect($response['status'])->toBeFalse()
            ->and($response['error'])->toContain('emirates id');
    });

    test('returns error when automation is disabled during execution', function () {
        disableNgiDeviceAutomation();

        $quote = createDeviceQuoteWithDependencies();
        $process = createDevicePolicyIssuanceProcess($quote);

        $ngiService = app(NgiInsuranceService::class);
        $response = $ngiService->executeSteps($process);

        expect($response['status'])->toBeFalse()
            ->and($response['error'])->toContain('disabled');
    });

    test('can check if policy issuance automation is enabled', function () {
        enableNgiDeviceAutomation();

        $ngiService = app(NgiInsuranceService::class);
        $isEnabled = $ngiService->isPolicyIssuanceAutomationEnabled();

        expect($isEnabled)->toBeTrue();
    });

    test('can check if policy issuance automation is disabled', function () {
        disableNgiDeviceAutomation();

        $ngiService = app(NgiInsuranceService::class);
        $isEnabled = $ngiService->isPolicyIssuanceAutomationEnabled();

        expect($isEnabled)->toBeFalse();
    });
});

describe('NgiInsuranceService Step Sequence', function () {
    test('can get next step in policy issuance sequence', function () {
        $ngiService = app(NgiInsuranceService::class);

        $nextStep = $ngiService->getNextStep(null);
        expect($nextStep)->toBe(NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE);

        $nextStep = $ngiService->getNextStep(NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE);
        expect($nextStep)->toBe(NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM);

        $nextStep = $ngiService->getNextStep(NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM);
        expect($nextStep)->toBe(NgiEnum::STEP_BOOK_POLICY);

        $nextStep = $ngiService->getNextStep(NgiEnum::STEP_BOOK_POLICY);
        expect($nextStep)->toBeNull(); // Last step
    });

    test('returns null for invalid step', function () {
        $ngiService = app(NgiInsuranceService::class);

        $nextStep = $ngiService->getNextStep('InvalidStep');

        expect($nextStep)->toBeNull();
    });
});

describe('NgiInsuranceService Insurer API Status', function () {
    test('returns correct insurer API status by step', function () {
        $ngiService = app(NgiInsuranceService::class);

        // When no step completed - should return policy issuance failed status
        $policyIssuance = (object) ['completed_step' => null];
        $status = $ngiService->getInsurerAPIStatusByStep($policyIssuance);
        expect($status)->toBe(PolicyIssuanceEnum::PIA_POLICY_ISSUANCE_API_FAILED_STATUS_ID);

        // When CreatePolicyFromQuote completed - should return get docs failed status
        $policyIssuance = (object) ['completed_step' => NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE];
        $status = $ngiService->getInsurerAPIStatusByStep($policyIssuance);
        expect($status)->toBe(PolicyIssuanceEnum::PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID);

        // When GetAndUploadPolicyDocs completed - should return book policy failed status
        $policyIssuance = (object) ['completed_step' => NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM];
        $status = $ngiService->getInsurerAPIStatusByStep($policyIssuance);
        expect($status)->toBe(PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID);

        // When all steps completed - should return null
        $policyIssuance = (object) ['completed_step' => NgiEnum::STEP_BOOK_POLICY];
        $status = $ngiService->getInsurerAPIStatusByStep($policyIssuance);
        expect($status)->toBeNull();
    });
});

describe('NgiInsuranceService Steps Locking Status', function () {
    test('returns all steps editable when throughAutomation is true', function () {
        $quote = createDeviceQuoteWithDependencies();

        $ngiService = app(NgiInsuranceService::class);
        $result = $ngiService->getStepsLockingStatus($quote, true);

        expect($result['isEditPolicyDetailsDisabled'])->toBeFalse()
            ->and($result['isEditBookingDetailsDisabled'])->toBeFalse()
            ->and($result['message'])->toBe(NgiEnum::ALL_STEPS_ARE_EDITABLE);
    });
});

// Helper functions for seeding lookups and creating test data

function seedNgiDeviceLookups(): array
{
    $db = \Illuminate\Support\Facades\DB::connection('sqlite');

    // Create Nationality
    $nationalityId = $db->table('nationality')->where('text', 'UAE')->value('id');
    if (! $nationalityId) {
        $nationalityId = $db->table('nationality')->insertGetId([
            'text' => 'UAE',
            'code' => 'UAE',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Create Insurance Provider (NGI)
    $insurerId = $db->table('insurance_provider')->where('code', 'NGI')->value('id');
    if (! $insurerId) {
        $insurerId = $db->table('insurance_provider')->insertGetId([
            'code' => 'NGI',
            'text' => 'National General Insurance',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    return [
        'nationality_id' => $nationalityId,
        'insurance_provider_id' => $insurerId,
    ];
}

function createDeviceQuoteWithDependencies()
{
    $lookups = seedNgiDeviceLookups();
    $quoteData = NgiPolicyIssuanceTestDataBuilder::buildDeviceQuoteData([], $lookups);
    $customerData = NgiPolicyIssuanceTestDataBuilder::buildCustomerData();
    $deviceSubQuoteData = NgiPolicyIssuanceTestDataBuilder::buildDeviceSubQuoteData();
    $paymentData = NgiPolicyIssuanceTestDataBuilder::buildPaymentData();

    $db = \Illuminate\Support\Facades\DB::connection('sqlite');

    // Create Customer
    $customerId = $db->table('customer')->insertGetId([
        'emirates_id_number' => $customerData['emirates_id_number'],
        'emirates_id_expiry_date' => $customerData['emirates_id_expiry_date'],
        'dob' => $customerData['dob'],
        'first_name' => $customerData['first_name'],
        'last_name' => $customerData['last_name'],
        'email' => $customerData['email'],
        'mobile_no' => $customerData['mobile_no'],
        'address' => $customerData['address'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create Personal Quote
    $quoteId = $db->table('personal_quotes')->insertGetId([
        'code' => $quoteData['code'],
        'uuid' => $quoteData['uuid'],
        'quote_type_id' => QuoteTypes::DEVICE->value,
        'first_name' => $quoteData['first_name'],
        'last_name' => $quoteData['last_name'],
        'email' => $quoteData['email'],
        'mobile_no' => $quoteData['mobile_no'],
        'dob' => $quoteData['dob'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Add additional device quote fields (simulating the device quote relationship)
    $db->table('device_quote')->insertGetId([
        'personal_quote_id' => $quoteId,
        'imei' => $deviceSubQuoteData['imei'],
        'device_make' => $deviceSubQuoteData['device_make'],
        'device_model' => $deviceSubQuoteData['device_model'],
        'device_type' => $deviceSubQuoteData['device_type'],
        'device_value' => $deviceSubQuoteData['device_value'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create Payment
    $db->table('payments')->insertGetId([
        'code' => $paymentData['code'],
        'payment_status_id' => $paymentData['payment_status_id'],
        'paymentable_type' => PersonalQuote::class,
        'paymentable_id' => $quoteId,
        'total_amount' => $paymentData['total_amount'],
        'is_main_lead_payment' => $paymentData['is_main_lead_payment'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create Insured record
    $insuredData = NgiPolicyIssuanceTestDataBuilder::buildInsuredData();
    $db->table('insured')->insertGetId([
        'insurable_type' => PersonalQuote::class,
        'insurable_id' => $quoteId,
        'id_type' => $insuredData['id_type'],
        'id_number' => $insuredData['id_number'],
        'first_name' => $insuredData['first_name'],
        'last_name' => $insuredData['last_name'],
        'email' => $insuredData['email'],
        'mobile_no' => $insuredData['mobile_no'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return PersonalQuote::on('sqlite')->find($quoteId);
}

function createDeviceQuoteWithoutCustomer()
{
    $lookups = seedNgiDeviceLookups();
    $quoteData = NgiPolicyIssuanceTestDataBuilder::buildDeviceQuoteData([], $lookups);

    $db = \Illuminate\Support\Facades\DB::connection('sqlite');

    // Create Personal Quote without customer
    $quoteId = $db->table('personal_quotes')->insertGetId([
        'code' => $quoteData['code'],
        'uuid' => $quoteData['uuid'],
        'quote_type_id' => QuoteTypes::DEVICE->value,
        'first_name' => $quoteData['first_name'],
        'last_name' => $quoteData['last_name'],
        'email' => $quoteData['email'],
        'mobile_no' => $quoteData['mobile_no'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return PersonalQuote::on('sqlite')->find($quoteId);
}

function createDeviceQuoteWithoutDeviceDetails()
{
    $lookups = seedNgiDeviceLookups();
    $quoteData = NgiPolicyIssuanceTestDataBuilder::buildDeviceQuoteData([], $lookups);
    $customerData = NgiPolicyIssuanceTestDataBuilder::buildCustomerData();

    $db = \Illuminate\Support\Facades\DB::connection('sqlite');

    // Create Customer
    $customerId = $db->table('customer')->insertGetId([
        'emirates_id_number' => $customerData['emirates_id_number'],
        'dob' => $customerData['dob'],
        'first_name' => $customerData['first_name'],
        'last_name' => $customerData['last_name'],
        'email' => $customerData['email'],
        'mobile_no' => $customerData['mobile_no'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create Personal Quote without device details
    $quoteId = $db->table('personal_quotes')->insertGetId([
        'code' => $quoteData['code'],
        'uuid' => $quoteData['uuid'],
        'quote_type_id' => QuoteTypes::DEVICE->value,
        'first_name' => $quoteData['first_name'],
        'last_name' => $quoteData['last_name'],
        'email' => $quoteData['email'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return PersonalQuote::on('sqlite')->find($quoteId);
}

function createDeviceQuoteWithoutImei()
{
    $lookups = seedNgiDeviceLookups();
    $quoteData = NgiPolicyIssuanceTestDataBuilder::buildDeviceQuoteData([], $lookups);
    $customerData = NgiPolicyIssuanceTestDataBuilder::buildCustomerData();

    $db = \Illuminate\Support\Facades\DB::connection('sqlite');

    // Create Customer
    $customerId = $db->table('customer')->insertGetId([
        'emirates_id_number' => $customerData['emirates_id_number'],
        'dob' => $customerData['dob'],
        'first_name' => $customerData['first_name'],
        'last_name' => $customerData['last_name'],
        'email' => $customerData['email'],
        'mobile_no' => $customerData['mobile_no'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create Personal Quote
    $quoteId = $db->table('personal_quotes')->insertGetId([
        'code' => $quoteData['code'],
        'uuid' => $quoteData['uuid'],
        'quote_type_id' => QuoteTypes::DEVICE->value,
        'first_name' => $quoteData['first_name'],
        'last_name' => $quoteData['last_name'],
        'email' => $quoteData['email'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create device quote without IMEI
    $db->table('device_quote')->insertGetId([
        'personal_quote_id' => $quoteId,
        'imei' => null, // Missing IMEI
        'device_make' => 'Apple',
        'device_model' => 'iPhone 15',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return PersonalQuote::on('sqlite')->find($quoteId);
}

function createDeviceQuoteWithoutInsurerQuoteNumber()
{
    $lookups = seedNgiDeviceLookups();
    $quoteData = NgiPolicyIssuanceTestDataBuilder::buildDeviceQuoteData(['insurer_quote_number' => null], $lookups);
    $customerData = NgiPolicyIssuanceTestDataBuilder::buildCustomerData();
    $deviceSubQuoteData = NgiPolicyIssuanceTestDataBuilder::buildDeviceSubQuoteData();
    $paymentData = NgiPolicyIssuanceTestDataBuilder::buildPaymentData();

    $db = \Illuminate\Support\Facades\DB::connection('sqlite');

    // Create Customer
    $customerId = $db->table('customer')->insertGetId([
        'emirates_id_number' => $customerData['emirates_id_number'],
        'dob' => $customerData['dob'],
        'first_name' => $customerData['first_name'],
        'last_name' => $customerData['last_name'],
        'email' => $customerData['email'],
        'mobile_no' => $customerData['mobile_no'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create Personal Quote without insurer_quote_number
    $quoteId = $db->table('personal_quotes')->insertGetId([
        'code' => $quoteData['code'],
        'uuid' => $quoteData['uuid'],
        'quote_type_id' => QuoteTypes::DEVICE->value,
        'first_name' => $quoteData['first_name'],
        'last_name' => $quoteData['last_name'],
        'email' => $quoteData['email'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create device quote with IMEI
    $db->table('device_quote')->insertGetId([
        'personal_quote_id' => $quoteId,
        'imei' => $deviceSubQuoteData['imei'],
        'device_make' => $deviceSubQuoteData['device_make'],
        'device_model' => $deviceSubQuoteData['device_model'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create Payment
    $db->table('payments')->insertGetId([
        'code' => $paymentData['code'],
        'payment_status_id' => $paymentData['payment_status_id'],
        'paymentable_type' => PersonalQuote::class,
        'paymentable_id' => $quoteId,
        'total_amount' => $paymentData['total_amount'],
        'is_main_lead_payment' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return PersonalQuote::on('sqlite')->find($quoteId);
}

function createDeviceQuoteWithCustomerMissingEmiratesId()
{
    $lookups = seedNgiDeviceLookups();
    $quoteData = NgiPolicyIssuanceTestDataBuilder::buildDeviceQuoteData([], $lookups);
    $customerData = NgiPolicyIssuanceTestDataBuilder::buildCustomerData(['emirates_id_number' => null]);
    $deviceSubQuoteData = NgiPolicyIssuanceTestDataBuilder::buildDeviceSubQuoteData();
    $paymentData = NgiPolicyIssuanceTestDataBuilder::buildPaymentData();

    $db = \Illuminate\Support\Facades\DB::connection('sqlite');

    // Create Customer without Emirates ID
    $customerId = $db->table('customer')->insertGetId([
        'emirates_id_number' => null,
        'dob' => $customerData['dob'],
        'first_name' => $customerData['first_name'],
        'last_name' => $customerData['last_name'],
        'email' => $customerData['email'],
        'mobile_no' => $customerData['mobile_no'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create Personal Quote
    $quoteId = $db->table('personal_quotes')->insertGetId([
        'code' => $quoteData['code'],
        'uuid' => $quoteData['uuid'],
        'quote_type_id' => QuoteTypes::DEVICE->value,
        'first_name' => $quoteData['first_name'],
        'last_name' => $quoteData['last_name'],
        'email' => $quoteData['email'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create device quote
    $db->table('device_quote')->insertGetId([
        'personal_quote_id' => $quoteId,
        'imei' => $deviceSubQuoteData['imei'],
        'device_make' => $deviceSubQuoteData['device_make'],
        'device_model' => $deviceSubQuoteData['device_model'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create Payment
    $db->table('payments')->insertGetId([
        'code' => $paymentData['code'],
        'payment_status_id' => $paymentData['payment_status_id'],
        'paymentable_type' => PersonalQuote::class,
        'paymentable_id' => $quoteId,
        'total_amount' => $paymentData['total_amount'],
        'is_main_lead_payment' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return PersonalQuote::on('sqlite')->find($quoteId);
}

function createDevicePolicyIssuanceProcess($quote)
{
    $lookups = seedNgiDeviceLookups();
    $db = \Illuminate\Support\Facades\DB::connection('sqlite');

    $processId = $db->table('policy_issuance')->insertGetId([
        'insurance_provider_id' => $lookups['insurance_provider_id'],
        'model_type' => PersonalQuote::class,
        'model_id' => $quote->id,
        'quote_type' => QuoteTypes::DEVICE->value,
        'status' => PolicyIssuanceEnum::PENDING_STATUS,
        'completed_step' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return PolicyIssuance::on('sqlite')->find($processId);
}

function getNgiInsuranceProvider()
{
    $lookups = seedNgiDeviceLookups();

    return InsuranceProvider::on('sqlite')->find($lookups['insurance_provider_id']);
}

function enableNgiDeviceAutomation()
{
    $db = \Illuminate\Support\Facades\DB::connection('sqlite');
    $db->table('application_storage')->updateOrInsert(
        ['key_name' => ApplicationStorageEnums::ENABLE_NGI_SMARTPHONE_POLICY_ISSUANCE],
        ['value' => '1', 'created_at' => now(), 'updated_at' => now()]
    );
}

function disableNgiDeviceAutomation()
{
    $db = \Illuminate\Support\Facades\DB::connection('sqlite');
    $db->table('application_storage')->updateOrInsert(
        ['key_name' => ApplicationStorageEnums::ENABLE_NGI_SMARTPHONE_POLICY_ISSUANCE],
        ['value' => '0', 'created_at' => now(), 'updated_at' => now()]
    );
}
