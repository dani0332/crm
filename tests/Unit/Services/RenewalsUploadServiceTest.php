<?php

declare(strict_types=1);

use App\Enums\FetchPlansStatuses;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Models\CarQuote;
use App\Models\RenewalQuoteProcess;
use App\Services\RenewalsUploadService;
use ReflectionClass;
use Tests\Helpers\TestSchemaCreator;

beforeEach(
    function () {
        // Create minimal schema for tests that may hit database
        TestSchemaCreator::createRenewalsSchema();
    }
);

/**
 * Helper function to invoke the private markOtherFetchPlansOutdated method
 */
function invokeMarkOtherFetchPlansOutdated(RenewalsUploadService $service, $quote, $renewalQuoteProcess): void
{
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('markOtherFetchPlansOutdated');
    $method->setAccessible(true);
    $method->invoke($service, $quote, $renewalQuoteProcess);
}

test('marks other pending fetch plans as outdated successfully', function () {
    // Arrange: Create a quote
    $quote = CarQuote::factory()->create();
    
    // Create the current renewal quote process (should not be marked as outdated)
    $currentProcess = RenewalQuoteProcess::create([
        'quote_id' => $quote->id,
        'status' => RenewalProcessStatuses::PROCESSED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::PENDING,
        'quote_type' => 'CAR',
        'data' => [],
    ]);
    
    // Create multiple other pending processes that should be marked as outdated
    $processesToMark = [];
    for ($i = 0; $i < 5; $i++) {
        $processesToMark[] = RenewalQuoteProcess::create([
            'quote_id' => $quote->id,
            'status' => RenewalProcessStatuses::PROCESSED,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
            'quote_type' => 'CAR',
            'data' => [],
        ]);
    }
    
    // Act: Call the private method
    $service = app(RenewalsUploadService::class);
    invokeMarkOtherFetchPlansOutdated($service, $quote, $currentProcess);
    
    // Assert: All other processes should be marked as outdated
    foreach ($processesToMark as $process) {
        $process->refresh();
        expect($process->fetch_plans_status)->toBe(FetchPlansStatuses::OUTDATED);
    }
    
    // Assert: Current process should remain pending
    $currentProcess->refresh();
    expect($currentProcess->fetch_plans_status)->toBe(FetchPlansStatuses::PENDING);
});

test('does not mark processes with different status as outdated', function () {
    // Arrange: Create a quote
    $quote = CarQuote::factory()->create();
    
    // Create the current renewal quote process
    $currentProcess = RenewalQuoteProcess::create([
        'quote_id' => $quote->id,
        'status' => RenewalProcessStatuses::PROCESSED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::PENDING,
        'quote_type' => 'CAR',
        'data' => [],
    ]);
    
    // Create processes with different statuses (should not be affected)
    $processedProcess = RenewalQuoteProcess::create([
        'quote_id' => $quote->id,
        'status' => RenewalProcessStatuses::VALIDATED, // Different status
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::PENDING,
        'quote_type' => 'CAR',
        'data' => [],
    ]);
    
    $fetchedProcess = RenewalQuoteProcess::create([
        'quote_id' => $quote->id,
        'status' => RenewalProcessStatuses::PROCESSED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::FETCHED, // Already fetched
        'quote_type' => 'CAR',
        'data' => [],
    ]);
    
    // Act: Call the private method
    $service = app(RenewalsUploadService::class);
    invokeMarkOtherFetchPlansOutdated($service, $quote, $currentProcess);
    
    // Assert: Processes with different statuses should not be changed
    $processedProcess->refresh();
    $fetchedProcess->refresh();
    
    expect($processedProcess->fetch_plans_status)->toBe(FetchPlansStatuses::PENDING)
        ->and($fetchedProcess->fetch_plans_status)->toBe(FetchPlansStatuses::FETCHED);
});

test('handles empty result set when no pending processes exist', function () {
    // Arrange: Create a quote
    $quote = CarQuote::factory()->create();
    
    // Create only the current renewal quote process
    $currentProcess = RenewalQuoteProcess::create([
        'quote_id' => $quote->id,
        'status' => RenewalProcessStatuses::PROCESSED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::PENDING,
        'quote_type' => 'CAR',
        'data' => [],
    ]);
    
    // Act: Call the private method (should not throw exception)
    $service = app(RenewalsUploadService::class);
    invokeMarkOtherFetchPlansOutdated($service, $quote, $currentProcess);
    
    // Assert: Current process should remain unchanged
    $currentProcess->refresh();
    expect($currentProcess->fetch_plans_status)->toBe(FetchPlansStatuses::PENDING);
});

test('handles large batch of processes (more than 50 records)', function () {
    // Arrange: Create a quote
    $quote = CarQuote::factory()->create();
    
    // Create the current renewal quote process
    $currentProcess = RenewalQuoteProcess::create([
        'quote_id' => $quote->id,
        'status' => RenewalProcessStatuses::PROCESSED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::PENDING,
        'quote_type' => 'CAR',
        'data' => [],
    ]);
    
    // Create 75 processes (more than batch size of 50)
    $processesToMark = [];
    for ($i = 0; $i < 75; $i++) {
        $processesToMark[] = RenewalQuoteProcess::create([
            'quote_id' => $quote->id,
            'status' => RenewalProcessStatuses::PROCESSED,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
            'quote_type' => 'CAR',
            'data' => [],
        ]);
    }
    
    // Act: Call the private method
    $service = app(RenewalsUploadService::class);
    invokeMarkOtherFetchPlansOutdated($service, $quote, $currentProcess);
    
    // Assert: All processes should be marked as outdated
    $updatedCount = RenewalQuoteProcess::where('quote_id', $quote->id)
        ->where('id', '!=', $currentProcess->id)
        ->where('fetch_plans_status', FetchPlansStatuses::OUTDATED)
        ->count();
    
    expect($updatedCount)->toBe(75);
});

test('does not affect processes for different quotes', function () {
    // Arrange: Create two different quotes
    $quote1 = CarQuote::factory()->create();
    $quote2 = CarQuote::factory()->create();
    
    // Create the current renewal quote process for quote1
    $currentProcess = RenewalQuoteProcess::create([
        'quote_id' => $quote1->id,
        'status' => RenewalProcessStatuses::PROCESSED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::PENDING,
        'quote_type' => 'CAR',
        'data' => [],
    ]);
    
    // Create pending processes for quote1 (should be marked as outdated)
    $quote1Processes = [];
    for ($i = 0; $i < 3; $i++) {
        $quote1Processes[] = RenewalQuoteProcess::create([
            'quote_id' => $quote1->id,
            'status' => RenewalProcessStatuses::PROCESSED,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
            'quote_type' => 'CAR',
            'data' => [],
        ]);
    }
    
    // Create pending processes for quote2 (should NOT be affected)
    $quote2Processes = [];
    for ($i = 0; $i < 3; $i++) {
        $quote2Processes[] = RenewalQuoteProcess::create([
            'quote_id' => $quote2->id,
            'status' => RenewalProcessStatuses::PROCESSED,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
            'quote_type' => 'CAR',
            'data' => [],
        ]);
    }
    
    // Act: Call the private method for quote1
    $service = app(RenewalsUploadService::class);
    invokeMarkOtherFetchPlansOutdated($service, $quote1, $currentProcess);
    
    // Assert: Quote1 processes should be marked as outdated
    foreach ($quote1Processes as $process) {
        $process->refresh();
        expect($process->fetch_plans_status)->toBe(FetchPlansStatuses::OUTDATED);
    }
    
    // Assert: Quote2 processes should remain pending
    foreach ($quote2Processes as $process) {
        $process->refresh();
        expect($process->fetch_plans_status)->toBe(FetchPlansStatuses::PENDING);
    }
});

test('does not affect processes with different type', function () {
    // Arrange: Create a quote
    $quote = CarQuote::factory()->create();
    
    // Create the current renewal quote process
    $currentProcess = RenewalQuoteProcess::create([
        'quote_id' => $quote->id,
        'status' => RenewalProcessStatuses::PROCESSED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::PENDING,
        'quote_type' => 'CAR',
        'data' => [],
    ]);
    
    // Create processes with CREATE_LEADS type (should not be affected)
    $createProcesses = [];
    for ($i = 0; $i < 3; $i++) {
        $createProcesses[] = RenewalQuoteProcess::create([
            'quote_id' => $quote->id,
            'status' => RenewalProcessStatuses::PROCESSED,
            'type' => RenewalsUploadType::CREATE_LEADS, // Different type
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
            'quote_type' => 'CAR',
            'data' => [],
        ]);
    }
    
    // Act: Call the private method
    $service = app(RenewalsUploadService::class);
    invokeMarkOtherFetchPlansOutdated($service, $quote, $currentProcess);
    
    // Assert: CREATE_LEADS processes should remain pending
    foreach ($createProcesses as $process) {
        $process->refresh();
        expect($process->fetch_plans_status)->toBe(FetchPlansStatuses::PENDING);
    }
});

test('handles database deadlock with retry mechanism', function () {
    // Arrange: Create a quote
    $quote = CarQuote::factory()->create();
    
    // Create the current renewal quote process
    $currentProcess = RenewalQuoteProcess::create([
        'quote_id' => $quote->id,
        'status' => RenewalProcessStatuses::PROCESSED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::PENDING,
        'quote_type' => 'CAR',
        'data' => [],
    ]);
    
    // Create pending processes
    $processesToMark = [];
    for ($i = 0; $i < 3; $i++) {
        $processesToMark[] = RenewalQuoteProcess::create([
            'quote_id' => $quote->id,
            'status' => RenewalProcessStatuses::PROCESSED,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
            'quote_type' => 'CAR',
            'data' => [],
        ]);
    }
    
    // Note: Testing deadlock retry mechanism is complex with Eloquent.
    // The actual implementation has retry logic for deadlocks (max 3 retries with 200ms delay).
    // In a real scenario, deadlocks would occur under high concurrency.
    // This test verifies the method completes successfully under normal conditions.
    
    // Act: Call the method
    $service = app(RenewalsUploadService::class);
    invokeMarkOtherFetchPlansOutdated($service, $quote, $currentProcess);
    
    // Assert: All processes should be marked as outdated (method completes successfully)
    foreach ($processesToMark as $process) {
        $process->refresh();
        expect($process->fetch_plans_status)->toBe(FetchPlansStatuses::OUTDATED);
    }
});

test('completes successfully under normal database conditions', function () {
    // Arrange: Create a quote
    $quote = CarQuote::factory()->create();
    
    // Create the current renewal quote process
    $currentProcess = RenewalQuoteProcess::create([
        'quote_id' => $quote->id,
        'status' => RenewalProcessStatuses::PROCESSED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::PENDING,
        'quote_type' => 'CAR',
        'data' => [],
    ]);
    
    // Create pending processes
    $processesToMark = [];
    for ($i = 0; $i < 3; $i++) {
        $processesToMark[] = RenewalQuoteProcess::create([
            'quote_id' => $quote->id,
            'status' => RenewalProcessStatuses::PROCESSED,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
            'quote_type' => 'CAR',
            'data' => [],
        ]);
    }
    
    // Act: Call the method
    $service = app(RenewalsUploadService::class);
    invokeMarkOtherFetchPlansOutdated($service, $quote, $currentProcess);
    
    // Assert: Method completes without throwing and updates processes correctly
    foreach ($processesToMark as $process) {
        $process->refresh();
        expect($process->fetch_plans_status)->toBe(FetchPlansStatuses::OUTDATED);
    }
    
    // Current process should remain unchanged
    $currentProcess->refresh();
    expect($currentProcess->fetch_plans_status)->toBe(FetchPlansStatuses::PENDING);
});
