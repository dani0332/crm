<?php

declare(strict_types=1);

use App\Enums\ApplicationStorageEnums;
use App\Enums\NgiEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Models\InsuranceProvider;
use App\Models\PersonalQuote;
use App\Models\PolicyIssuance;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiBookPolicyService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiInsuranceService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiResponseHandler;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiStepExecutor;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiValidationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\NgiPolicyIssuanceTestDataBuilder;

beforeEach(function () {
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
        $quote = new PersonalQuote;
        $quote->id = 1;
        $quote->plan_id = 1001;
        $quote->code = 'DEV-VALID-0001';
        $quote->uuid = 'test-uuid-ngi-validation';
        $quote->insurer_quote_number = 'NGI-Q-1234';
        $customer = (object) [
            'id' => 1,
            'emirates_id_number' => '784-1234-12345678-1',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'mobile_no' => '+971501000000',
        ];
        $deviceQuote = (object) [
            'id' => 1,
            'imei' => '123456789012345',
        ];
        $latestInsured = (object) [
            'id' => 1,
            'id_type' => 'emiratesId',
            'id_number' => '784-1234-12345678-1',
        ];
        $quote->setRelation('customer', $customer);
        $quote->setRelation('deviceQuote', $deviceQuote);
        $quote->setRelation('latestInsured', $latestInsured);

        $process = new class($quote)
        {
            public int $id = 1;
            public string $status = PolicyIssuanceEnum::PENDING_STATUS;
            public ?string $completed_step = null;
            public object $model;

            public function __construct(object $quote)
            {
                $this->model = $quote;
            }

            public function update(array $attributes): bool
            {
                foreach ($attributes as $key => $value) {
                    $this->{$key} = $value;
                }

                return true;
            }

            public function refresh(): void {}
        };

        $validationService = Mockery::mock(NgiValidationService::class);
        $validationService->shouldReceive('validateRequiredData')
            ->once()
            ->with($quote, $quote->customer, $quote->deviceQuote, $quote->latestInsured)
            ->andReturn(['status' => true]);

        $stepExecutor = Mockery::mock(NgiStepExecutor::class);
        $stepExecutor->shouldReceive('executeCreatePolicyFromQuoteStep')
            ->once()
            ->with($quote, $process)
            ->andReturn([
                'status' => true,
                'completed_step' => NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
            ]);
        $stepExecutor->shouldReceive('executeGetPolicyDocumentsAndUploadToIMCRMStep')
            ->once()
            ->with($quote, $process)
            ->andReturn([
                'status' => true,
                'completed_step' => NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
            ]);
        $stepExecutor->shouldReceive('executeBookPolicyStep')
            ->once()
            ->with($quote, $process)
            ->andReturn([
                'status' => true,
                'completed_step' => NgiEnum::STEP_BOOK_POLICY,
            ]);

        $bookPolicyService = Mockery::mock(NgiBookPolicyService::class);
        $bookPolicyService->shouldIgnoreMissing();

        $responseHandler = Mockery::mock(NgiResponseHandler::class);
        $responseHandler->shouldIgnoreMissing();

        $ngiService = Mockery::mock(
            NgiInsuranceService::class,
            [$stepExecutor, $validationService, $bookPolicyService, $responseHandler]
        )->makePartial();
        $ngiService->shouldReceive('isPolicyIssuanceAutomationEnabled')->andReturn(true);

        $response = $ngiService->executeSteps($process);

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
    $db = DB::connection('sqlite');

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

    $db = DB::connection('sqlite');

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
        'customer_id' => $customerId,
        'insurer_quote_number' => $quoteData['insurer_quote_number'],
        'first_name' => $quoteData['first_name'],
        'last_name' => $quoteData['last_name'],
        'email' => $quoteData['email'],
        'mobile_no' => $quoteData['mobile_no'],
        'dob' => $quoteData['dob'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Add additional device quote fields (simulating the device quote relationship)
    $db->table('device_quote_request')->insertGetId([
        'personal_quote_id' => $quoteId,
        'imei' => $deviceSubQuoteData['imei'],
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

    $db = DB::connection('sqlite');

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

    $db = DB::connection('sqlite');

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
        'customer_id' => $customerId,
        'insurer_quote_number' => $quoteData['insurer_quote_number'],
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
    $deviceSubQuoteData = NgiPolicyIssuanceTestDataBuilder::buildDeviceSubQuoteData();
    $paymentData = NgiPolicyIssuanceTestDataBuilder::buildPaymentData();

    $db = DB::connection('sqlite');

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
        'customer_id' => $customerId,
        'insurer_quote_number' => $quoteData['insurer_quote_number'],
        'first_name' => $quoteData['first_name'],
        'last_name' => $quoteData['last_name'],
        'email' => $quoteData['email'],
        'mobile_no' => $quoteData['mobile_no'],
        'dob' => $quoteData['dob'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create device quote without IMEI
    $db->table('device_quote_request')->insertGetId([
        'personal_quote_id' => $quoteId,
        'imei' => null, // Missing IMEI
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

function createDeviceQuoteWithoutInsurerQuoteNumber()
{
    $lookups = seedNgiDeviceLookups();
    $quoteData = NgiPolicyIssuanceTestDataBuilder::buildDeviceQuoteData(['insurer_quote_number' => null], $lookups);
    $customerData = NgiPolicyIssuanceTestDataBuilder::buildCustomerData();
    $deviceSubQuoteData = NgiPolicyIssuanceTestDataBuilder::buildDeviceSubQuoteData();
    $paymentData = NgiPolicyIssuanceTestDataBuilder::buildPaymentData();

    $db = DB::connection('sqlite');

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
        'customer_id' => $customerId,
        'insurer_quote_number' => $quoteData['insurer_quote_number'], // This will be null
        'first_name' => $quoteData['first_name'],
        'last_name' => $quoteData['last_name'],
        'email' => $quoteData['email'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create device quote with IMEI
    $db->table('device_quote_request')->insertGetId([
        'personal_quote_id' => $quoteId,
        'imei' => $deviceSubQuoteData['imei'],
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

    $db = DB::connection('sqlite');

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
        'customer_id' => $customerId,
        'insurer_quote_number' => $quoteData['insurer_quote_number'],
        'first_name' => $quoteData['first_name'],
        'last_name' => $quoteData['last_name'],
        'email' => $quoteData['email'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create device quote
    $db->table('device_quote_request')->insertGetId([
        'personal_quote_id' => $quoteId,
        'imei' => $deviceSubQuoteData['imei'],
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
    $db = DB::connection('sqlite');

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
    $db = DB::connection('sqlite');
    $db->table('application_storage')->updateOrInsert(
        ['key_name' => ApplicationStorageEnums::ENABLE_NGI_SMARTPHONE_POLICY_ISSUANCE],
        ['value' => '1', 'created_at' => now(), 'updated_at' => now()]
    );
}

function disableNgiDeviceAutomation()
{
    $db = DB::connection('sqlite');
    $db->table('application_storage')->updateOrInsert(
        ['key_name' => ApplicationStorageEnums::ENABLE_NGI_SMARTPHONE_POLICY_ISSUANCE],
        ['value' => '0', 'created_at' => now(), 'updated_at' => now()]
    );
}
