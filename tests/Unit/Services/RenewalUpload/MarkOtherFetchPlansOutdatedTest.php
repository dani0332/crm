<?php

declare(strict_types=1);

use App\Enums\FetchPlansStatuses;
use App\Enums\LeadSourceEnum;
use App\Enums\RenewalProcessStatuses;
use App\Enums\RenewalsUploadType;
use App\Models\CarQuote;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalsUploadLeads;
use App\Services\RenewalsUploadService;
use Tests\Helpers\TestSchemaCreator;

beforeEach(
    function () {
        // Create minimal schema for tests that may hit database
        TestSchemaCreator::createRenewalsSchema();
    }
);

afterEach(function () {
    Mockery::close();
});

if (! function_exists('createRenewalsUploadServiceWithMocks')) {
    /**
     * Create RenewalsUploadService instance using reflection to bypass constructor.
     * This prevents CapiRequestService and other dependencies from being autoloaded.
     *
     * Performance note: ReflectionClass has minimal overhead (~0.01-0.05ms per call).
     * We cache the ReflectionClass instance to avoid recreating it for each test.
     */
    function createRenewalsUploadServiceWithMocks(): RenewalsUploadService
    {
        static $reflection = null;

        // Cache ReflectionClass instance to avoid recreating it for each test call
        if ($reflection === null) {
            $reflection = new ReflectionClass(RenewalsUploadService::class);
        }

        // Create instance without calling constructor (fast operation)
        $service = $reflection->newInstanceWithoutConstructor();

        return $service;
    }
}

test('marks other pending fetch plans as outdated successfully', function () {
    // Arrange: Create a simple quote
    $quote = CarQuote::factory()->create([
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
    ]);

    // Create renewal upload lead
    $renewalUploadLead = RenewalsUploadLeads::create([
        'file_name' => 'test.xlsx',
        'file_path' => 'test/path.xlsx',
        'quote_type' => 'CAR',
        'status' => 'IN_PROGRESS',
        'renewal_import_type' => RenewalsUploadType::UPDATE_LEADS,
        'is_sic' => 0,
    ]);

    // Create the current renewal quote process (should not be marked as outdated)
    $currentProcess = RenewalQuoteProcess::create([
        'renewals_upload_lead_id' => $renewalUploadLead->id,
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
            'renewals_upload_lead_id' => $renewalUploadLead->id,
            'quote_id' => $quote->id,
            'status' => RenewalProcessStatuses::PROCESSED,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
            'quote_type' => 'CAR',
            'data' => [],
        ]);
    }

    // Act: Call markOtherFetchPlansOutdated directly
    $service = createRenewalsUploadServiceWithMocks();
    $service->markOtherFetchPlansOutdated($quote, $currentProcess);

    // Assert: All other processes should be marked as outdated
    foreach ($processesToMark as $process) {
        $process->refresh();
        expect($process->fetch_plans_status)->toBe(FetchPlansStatuses::OUTDATED);
    }

    // Assert: Current process should NOT be marked as outdated
    $currentProcess->refresh();
    expect($currentProcess->fetch_plans_status)->toBe(FetchPlansStatuses::PENDING);
});

test('does not mark processes with different status as outdated', function () {
    // Arrange: Create a simple quote
    $quote = CarQuote::factory()->create([
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
    ]);

    // Create renewal upload lead
    $renewalUploadLead = RenewalsUploadLeads::create([
        'file_name' => 'test.xlsx',
        'file_path' => 'test/path.xlsx',
        'quote_type' => 'CAR',
        'status' => 'IN_PROGRESS',
        'renewal_import_type' => RenewalsUploadType::UPDATE_LEADS,
        'is_sic' => 0,
    ]);

    // Create the current renewal quote process
    $currentProcess = RenewalQuoteProcess::create([
        'renewals_upload_lead_id' => $renewalUploadLead->id,
        'quote_id' => $quote->id,
        'status' => RenewalProcessStatuses::PROCESSED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::PENDING,
        'quote_type' => 'CAR',
        'data' => [],
    ]);

    // Create processes with different statuses (should not be affected)
    $processedProcess = RenewalQuoteProcess::create([
        'renewals_upload_lead_id' => $renewalUploadLead->id,
        'quote_id' => $quote->id,
        'status' => RenewalProcessStatuses::VALIDATED, // Different status
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::PENDING,
        'quote_type' => 'CAR',
        'data' => [],
    ]);

    $fetchedProcess = RenewalQuoteProcess::create([
        'renewals_upload_lead_id' => $renewalUploadLead->id,
        'quote_id' => $quote->id,
        'status' => RenewalProcessStatuses::PROCESSED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::FETCHED, // Already fetched
        'quote_type' => 'CAR',
        'data' => [],
    ]);

    // Act: Call markOtherFetchPlansOutdated directly
    $service = createRenewalsUploadServiceWithMocks();
    $service->markOtherFetchPlansOutdated($quote, $currentProcess);

    // Assert: Processes with different statuses should not be changed
    $processedProcess->refresh();
    $fetchedProcess->refresh();

    expect($processedProcess->fetch_plans_status)->toBe(FetchPlansStatuses::PENDING)
        ->and($fetchedProcess->fetch_plans_status)->toBe(FetchPlansStatuses::FETCHED);
});

test('handles empty result set when no pending processes exist', function () {
    // Arrange: Create a simple quote
    $quote = CarQuote::factory()->create([
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
    ]);

    // Create renewal upload lead
    $renewalUploadLead = RenewalsUploadLeads::create([
        'file_name' => 'test.xlsx',
        'file_path' => 'test/path.xlsx',
        'quote_type' => 'CAR',
        'status' => 'IN_PROGRESS',
        'renewal_import_type' => RenewalsUploadType::UPDATE_LEADS,
        'is_sic' => 0,
    ]);

    // Create only the current renewal quote process
    $currentProcess = RenewalQuoteProcess::create([
        'renewals_upload_lead_id' => $renewalUploadLead->id,
        'quote_id' => $quote->id,
        'status' => RenewalProcessStatuses::PROCESSED,
        'type' => RenewalsUploadType::UPDATE_LEADS,
        'fetch_plans_status' => FetchPlansStatuses::PENDING,
        'quote_type' => 'CAR',
        'data' => [],
    ]);

    // Act: Call markOtherFetchPlansOutdated (should not throw exception when no other processes exist)
    $service = createRenewalsUploadServiceWithMocks();
    $service->markOtherFetchPlansOutdated($quote, $currentProcess);

    // Assert: Current process should remain unchanged
    $currentProcess->refresh();
    expect($currentProcess->fetch_plans_status)->toBe(FetchPlansStatuses::PENDING);
});

test('handles large batch of processes (more than 50 records)', function () {
    // Arrange: Create a simple quote
    $quote = CarQuote::factory()->create([
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
    ]);

    // Create renewal upload lead
    $renewalUploadLead = RenewalsUploadLeads::create([
        'file_name' => 'test.xlsx',
        'file_path' => 'test/path.xlsx',
        'quote_type' => 'CAR',
        'status' => 'IN_PROGRESS',
        'renewal_import_type' => RenewalsUploadType::UPDATE_LEADS,
        'is_sic' => 0,
    ]);

    // Create the current renewal quote process
    $currentProcess = RenewalQuoteProcess::create([
        'renewals_upload_lead_id' => $renewalUploadLead->id,
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
            'renewals_upload_lead_id' => $renewalUploadLead->id,
            'quote_id' => $quote->id,
            'status' => RenewalProcessStatuses::PROCESSED,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
            'quote_type' => 'CAR',
            'data' => [],
        ]);
    }

    // Act: Call markOtherFetchPlansOutdated directly
    $service = createRenewalsUploadServiceWithMocks();
    $service->markOtherFetchPlansOutdated($quote, $currentProcess);

    // Assert: All processes should be marked as outdated
    $updatedCount = RenewalQuoteProcess::where('quote_id', $quote->id)
        ->where('id', '!=', $currentProcess->id)
        ->where('fetch_plans_status', FetchPlansStatuses::OUTDATED)
        ->count();

    expect($updatedCount)->toBe(75);
});

test('does not affect processes for different quotes', function () {
    // Arrange: Create two different quotes
    $quote1 = CarQuote::factory()->create([
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
    ]);

    $quote2 = CarQuote::factory()->create([
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
    ]);

    // Create renewal upload lead for quote1
    $renewalUploadLead1 = RenewalsUploadLeads::create([
        'file_name' => 'test1.xlsx',
        'file_path' => 'test/path1.xlsx',
        'quote_type' => 'CAR',
        'status' => 'IN_PROGRESS',
        'renewal_import_type' => RenewalsUploadType::UPDATE_LEADS,
        'is_sic' => 0,
    ]);

    // Create the current renewal quote process for quote1
    $currentProcess = RenewalQuoteProcess::create([
        'renewals_upload_lead_id' => $renewalUploadLead1->id,
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
            'renewals_upload_lead_id' => $renewalUploadLead1->id,
            'quote_id' => $quote1->id,
            'status' => RenewalProcessStatuses::PROCESSED,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
            'quote_type' => 'CAR',
            'data' => [],
        ]);
    }

    // Create renewal upload lead for quote2
    $renewalUploadLead2 = RenewalsUploadLeads::create([
        'file_name' => 'test2.xlsx',
        'file_path' => 'test/path2.xlsx',
        'quote_type' => 'CAR',
        'status' => 'IN_PROGRESS',
        'renewal_import_type' => RenewalsUploadType::UPDATE_LEADS,
        'is_sic' => 0,
    ]);

    // Create pending processes for quote2 (should NOT be affected)
    $quote2Processes = [];
    for ($i = 0; $i < 3; $i++) {
        $quote2Processes[] = RenewalQuoteProcess::create([
            'renewals_upload_lead_id' => $renewalUploadLead2->id,
            'quote_id' => $quote2->id,
            'status' => RenewalProcessStatuses::PROCESSED,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
            'quote_type' => 'CAR',
            'data' => [],
        ]);
    }

    // Act: Call markOtherFetchPlansOutdated for quote1
    $service = createRenewalsUploadServiceWithMocks();
    $service->markOtherFetchPlansOutdated($quote1, $currentProcess);

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
    // Arrange: Create a simple quote
    $quote = CarQuote::factory()->create([
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
    ]);

    // Create renewal upload lead
    $renewalUploadLead = RenewalsUploadLeads::create([
        'file_name' => 'test.xlsx',
        'file_path' => 'test/path.xlsx',
        'quote_type' => 'CAR',
        'status' => 'IN_PROGRESS',
        'renewal_import_type' => RenewalsUploadType::UPDATE_LEADS,
        'is_sic' => 0,
    ]);

    // Create the current renewal quote process
    $currentProcess = RenewalQuoteProcess::create([
        'renewals_upload_lead_id' => $renewalUploadLead->id,
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
            'renewals_upload_lead_id' => $renewalUploadLead->id,
            'quote_id' => $quote->id,
            'status' => RenewalProcessStatuses::PROCESSED,
            'type' => RenewalsUploadType::CREATE_LEADS, // Different type
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
            'quote_type' => 'CAR',
            'data' => [],
        ]);
    }

    // Act: Call markOtherFetchPlansOutdated directly
    $service = createRenewalsUploadServiceWithMocks();
    $service->markOtherFetchPlansOutdated($quote, $currentProcess);

    // Assert: CREATE_LEADS processes should remain pending
    foreach ($createProcesses as $process) {
        $process->refresh();
        expect($process->fetch_plans_status)->toBe(FetchPlansStatuses::PENDING);
    }
});

test('handles processing multiple records successfully', function () {
    // Arrange: Create a simple quote
    $quote = CarQuote::factory()->create([
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
    ]);

    // Create renewal upload lead
    $renewalUploadLead = RenewalsUploadLeads::create([
        'file_name' => 'test.xlsx',
        'file_path' => 'test/path.xlsx',
        'quote_type' => 'CAR',
        'status' => 'IN_PROGRESS',
        'renewal_import_type' => RenewalsUploadType::UPDATE_LEADS,
        'is_sic' => 0,
    ]);

    // Create the current renewal quote process
    $currentProcess = RenewalQuoteProcess::create([
        'renewals_upload_lead_id' => $renewalUploadLead->id,
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
            'renewals_upload_lead_id' => $renewalUploadLead->id,
            'quote_id' => $quote->id,
            'status' => RenewalProcessStatuses::PROCESSED,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
            'quote_type' => 'CAR',
            'data' => [],
        ]);
    }

    // Act: Call markOtherFetchPlansOutdated directly
    $service = createRenewalsUploadServiceWithMocks();
    $service->markOtherFetchPlansOutdated($quote, $currentProcess);

    // Assert: All processes should be marked as outdated
    foreach ($processesToMark as $process) {
        $process->refresh();
        expect($process->fetch_plans_status)->toBe(FetchPlansStatuses::OUTDATED);
    }

    // Current process should remain unchanged
    $currentProcess->refresh();
    expect($currentProcess->fetch_plans_status)->toBe(FetchPlansStatuses::PENDING);
});

test('completes successfully under normal database conditions', function () {
    // Arrange: Create a simple quote
    $quote = CarQuote::factory()->create([
        'source' => LeadSourceEnum::RENEWAL_UPLOAD,
    ]);

    // Create renewal upload lead
    $renewalUploadLead = RenewalsUploadLeads::create([
        'file_name' => 'test.xlsx',
        'file_path' => 'test/path.xlsx',
        'quote_type' => 'CAR',
        'status' => 'IN_PROGRESS',
        'renewal_import_type' => RenewalsUploadType::UPDATE_LEADS,
        'is_sic' => 0,
    ]);

    // Create the current renewal quote process
    $currentProcess = RenewalQuoteProcess::create([
        'renewals_upload_lead_id' => $renewalUploadLead->id,
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
            'renewals_upload_lead_id' => $renewalUploadLead->id,
            'quote_id' => $quote->id,
            'status' => RenewalProcessStatuses::PROCESSED,
            'type' => RenewalsUploadType::UPDATE_LEADS,
            'fetch_plans_status' => FetchPlansStatuses::PENDING,
            'quote_type' => 'CAR',
            'data' => [],
        ]);
    }

    // Act: Call markOtherFetchPlansOutdated directly
    $service = createRenewalsUploadServiceWithMocks();
    $service->markOtherFetchPlansOutdated($quote, $currentProcess);

    // Assert: Method completes without throwing and updates processes correctly
    foreach ($processesToMark as $process) {
        $process->refresh();
        expect($process->fetch_plans_status)->toBe(FetchPlansStatuses::OUTDATED);
    }

    // Current process should remain unchanged
    $currentProcess->refresh();
    expect($currentProcess->fetch_plans_status)->toBe(FetchPlansStatuses::PENDING);
});
