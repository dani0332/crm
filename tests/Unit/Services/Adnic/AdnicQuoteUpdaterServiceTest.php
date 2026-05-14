<?php

declare(strict_types=1);

use App\Enums\PolicyIssuanceStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\HealthQuote;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicQuoteUpdaterService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->service = new AdnicQuoteUpdaterService;
});

afterEach(function () {
    Mockery::close();
});

// CRITICAL TEST: Service initialization
test('quote updater service initializes correctly', function () {
    expect($this->service)->toBeInstanceOf(AdnicQuoteUpdaterService::class);
});

// CRITICAL TEST: Update quote from issue policy response updates all fields
test('update quote from issue policy response updates policy fields', function () {
    // Create mock quote
    $quote = Mockery::mock(HealthQuote::class);
    $quote->shouldReceive('update')
        ->once()
        ->with(Mockery::on(function ($data) {
            return isset($data['policy_number'])
                && isset($data['policy_start_date'])
                && isset($data['policy_expiry_date'])
                && isset($data['quote_status_id'])
                && isset($data['policy_issuance_status_id'])
                && isset($data['quote_status_date'])
                && $data['policy_number'] === 'POL123'
                && Carbon::parse($data['policy_start_date'])->toDateString() === '2024-01-01'
                && Carbon::parse($data['policy_expiry_date'])->toDateString() === '2024-12-31'
                && $data['quote_status_id'] === QuoteStatusEnum::PolicyIssued
                && $data['policy_issuance_status_id'] === PolicyIssuanceStatusEnum::PolicyIssued
                && $data['quote_status_date'] instanceof Carbon;
        }))
        ->andReturn(true);

    // Create mock issue policy result
    $issuePolicyResult = new stdClass;
    $issuePolicyResult->PolicyInfo = new stdClass;
    $issuePolicyResult->PolicyInfo->PolicyNo = 'POL123';
    $issuePolicyResult->PolicyInfo->PolicyStartDate = '2024-01-01';
    $issuePolicyResult->PolicyInfo->PolicyEndDate = '2024-12-31';

    $this->service->updateQuoteFromIssuePolicyResponse($quote, $issuePolicyResult);

    expect(true)->toBeTrue(); // If we reach here, the mock assertions passed
});

// CRITICAL TEST: Update quote filters out null values
test('update quote from issue policy response filters null values', function () {
    $quote = Mockery::mock(HealthQuote::class);
    $quote->shouldReceive('update')
        ->once()
        ->with(Mockery::on(function ($data) {
            // Should only have non-null fields
            return isset($data['policy_number'])
                && $data['policy_number'] === 'POL456'
                && ! isset($data['policy_start_date']) // null should be filtered out
                && isset($data['quote_status_id'])
                && isset($data['policy_issuance_status_id']);
        }))
        ->andReturn(true);

    $issuePolicyResult = new stdClass;
    $issuePolicyResult->PolicyInfo = new stdClass;
    $issuePolicyResult->PolicyInfo->PolicyNo = 'POL456';
    $issuePolicyResult->PolicyInfo->PolicyStartDate = null;
    $issuePolicyResult->PolicyInfo->PolicyEndDate = null;

    $this->service->updateQuoteFromIssuePolicyResponse($quote, $issuePolicyResult);

    expect(true)->toBeTrue();
});

// CRITICAL TEST: Update quote sets quote status to PolicyIssued
test('update quote from issue policy response sets correct status', function () {
    $quote = Mockery::mock(HealthQuote::class);
    $quote->shouldReceive('update')
        ->once()
        ->with(Mockery::on(function ($data) {
            return $data['quote_status_id'] === QuoteStatusEnum::PolicyIssued
                && $data['policy_issuance_status_id'] === PolicyIssuanceStatusEnum::PolicyIssued;
        }))
        ->andReturn(true);

    $issuePolicyResult = new stdClass;
    $issuePolicyResult->PolicyInfo = new stdClass;
    $issuePolicyResult->PolicyInfo->PolicyNo = 'POL789';
    $issuePolicyResult->PolicyInfo->PolicyStartDate = '2024-01-01';
    $issuePolicyResult->PolicyInfo->PolicyEndDate = '2024-12-31';

    $this->service->updateQuoteFromIssuePolicyResponse($quote, $issuePolicyResult);

    expect(true)->toBeTrue();
});

// CRITICAL TEST: Update quote handles missing PolicyInfo gracefully
test('update quote from issue policy response handles missing policy info', function () {
    $quote = Mockery::mock(HealthQuote::class);
    $quote->shouldReceive('update')
        ->once()
        ->with(Mockery::on(function ($data) {
            // Should still update status fields even if policy info is missing
            return isset($data['quote_status_id'])
                && isset($data['policy_issuance_status_id'])
                && isset($data['quote_status_date'])
                && ! isset($data['policy_number']); // Should not set policy_number when null
        }))
        ->andReturn(true);

    $issuePolicyResult = new stdClass;
    $issuePolicyResult->PolicyInfo = new stdClass;
    $issuePolicyResult->PolicyInfo->PolicyNo = null;
    $issuePolicyResult->PolicyInfo->PolicyStartDate = null;
    $issuePolicyResult->PolicyInfo->PolicyEndDate = null;

    $this->service->updateQuoteFromIssuePolicyResponse($quote, $issuePolicyResult);

    expect(true)->toBeTrue();
});

// CRITICAL TEST: Update payment method exists but is commented out
test('update payment from issue policy response method exists', function () {
    expect(method_exists($this->service, 'updatePaymentFromIssuePolicyResponse'))->toBeTrue();
});

// CRITICAL TEST: Update payment method signature
test('update payment from issue policy response has correct signature', function () {
    $reflection = new ReflectionClass($this->service);
    $method = $reflection->getMethod('updatePaymentFromIssuePolicyResponse');

    expect($method->getNumberOfParameters())->toBe(2);
});

// CRITICAL TEST: Quote status date is set to current time
test('update quote from issue policy response sets quote status date', function () {
    $quote = Mockery::mock(HealthQuote::class);
    $quote->shouldReceive('update')
        ->once()
        ->with(Mockery::on(function ($data) {
            return isset($data['quote_status_date'])
                && $data['quote_status_date'] instanceof Carbon;
        }))
        ->andReturn(true);

    $issuePolicyResult = new stdClass;
    $issuePolicyResult->PolicyInfo = new stdClass;
    $issuePolicyResult->PolicyInfo->PolicyNo = 'POL999';
    $issuePolicyResult->PolicyInfo->PolicyStartDate = '2024-01-01';
    $issuePolicyResult->PolicyInfo->PolicyEndDate = '2024-12-31';

    $this->service->updateQuoteFromIssuePolicyResponse($quote, $issuePolicyResult);

    expect(true)->toBeTrue();
});

// CRITICAL TEST: Handles partial PolicyInfo data
test('update quote from issue policy response handles partial policy info', function () {
    $quote = Mockery::mock(HealthQuote::class);
    $quote->shouldReceive('update')
        ->once()
        ->with(Mockery::on(function ($data) {
            // Should only have non-null fields from PolicyInfo
            return isset($data['policy_number'])
                && $data['policy_number'] === 'POL-PARTIAL'
                && ! isset($data['policy_start_date']) // Filtered out because null
                && ! isset($data['policy_expiry_date']) // Filtered out because null
                && isset($data['quote_status_id'])
                && isset($data['policy_issuance_status_id'])
                && isset($data['quote_status_date']);
        }))
        ->andReturn(true);

    $issuePolicyResult = new stdClass;
    $issuePolicyResult->PolicyInfo = new stdClass;
    $issuePolicyResult->PolicyInfo->PolicyNo = 'POL-PARTIAL';
    $issuePolicyResult->PolicyInfo->PolicyStartDate = null;
    $issuePolicyResult->PolicyInfo->PolicyEndDate = null;

    $this->service->updateQuoteFromIssuePolicyResponse($quote, $issuePolicyResult);

    expect(true)->toBeTrue();
});
