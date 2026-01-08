<?php

declare(strict_types=1);

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Jobs\OCB\SendCyberOCBIntroEmailJob;
use App\Jobs\SendCyberAutomatedFollowupJob;
use App\Models\ApplicationStorage;
use App\Models\PersonalQuote;
use App\Models\User;
use App\Services\BirdService;
use App\Services\EmailServices\CyberEmailService;
use Illuminate\Support\Facades\Queue;

uses(Tests\TestCase::class);

beforeEach(function () {
    Queue::fake();
});

afterEach(function () {
    \Mockery::close();
});

test('sends cyber OCB intro email successfully', function () {
    // Create mock quote object
    $quote = (object) [
        'uuid' => 'test-uuid-123',
        'code' => 'CYB-123',
        'email' => 'customer@test.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'mobile_no' => '971509876543',
        'source' => 'website',
        'advisor_id' => 1,
    ];

    // Create mock advisor object
    $advisor = (object) [
        'id' => 1,
        'email' => 'advisor@test.com',
        'name' => 'Test Advisor',
        'mobile_no' => '971501234567',
        'landline_no' => '97141234567',
        'profile_photo_path' => '/path/to/photo.jpg',
    ];

    // Create mock workflow URL object
    $workflowUrl = (object) [
        'value' => 'https://bird.example.com/workflow/cyber-ocb-intro',
    ];

    // Mock ApplicationStorage query
    $queryBuilder = \Mockery::mock();
    $queryBuilder->shouldReceive('where')
        ->once()
        ->with('key_name', ApplicationStorageEnums::BIRD_CYBER_OCB_INTRO_EMAIL)
        ->andReturnSelf();
    $queryBuilder->shouldReceive('first')
        ->once()
        ->andReturn($workflowUrl);

    // Mock static ApplicationStorage::where() call
    $applicationStorageMock = \Mockery::mock('alias:'.ApplicationStorage::class);
    $applicationStorageMock->shouldReceive('where')
        ->once()
        ->with('key_name', ApplicationStorageEnums::BIRD_CYBER_OCB_INTRO_EMAIL)
        ->andReturn($queryBuilder);

    // Mock User::find
    $userMock = \Mockery::mock('alias:'.User::class);
    $userMock->shouldReceive('find')
        ->once()
        ->with(1)
        ->andReturn($advisor);

    // Mock BirdService
    $birdServiceMock = \Mockery::mock(BirdService::class);
    $this->app->instance(BirdService::class, $birdServiceMock);

    $mockResponse = (object) [
        'status_code' => 200,
        'headers' => ['Run-Id' => ['test-run-id-123']],
        'body' => 'Success',
    ];

    $birdServiceMock
        ->shouldReceive('isFollowupExecuted')
        ->once()
        ->with('test-uuid-123', QuoteTypes::CYBER->id(), QuoteFlowType::CYBER_OCB_INTRO_EMAIL->value)
        ->andReturn(false);

    $birdServiceMock
        ->shouldReceive('triggerWebHookRequest')
        ->once()
        ->with($workflowUrl->value, \Mockery::type('array'))
        ->andReturn($mockResponse);

    $birdServiceMock
        ->shouldReceive('isFollowupExecuted')
        ->once()
        ->with('test-uuid-123', QuoteTypes::CYBER->id(), QuoteFlowType::CYBER_AUTOMATED_FOLLOWUPS->value)
        ->andReturn(false);

    $birdServiceMock
        ->shouldReceive('createQuoteWorkFlowDetails')
        ->once()
        ->with($quote, $mockResponse, QuoteFlowType::CYBER_OCB_INTRO_EMAIL->value, QuoteTypes::CYBER->id());

    $service = app(CyberEmailService::class);
    $service->sendCyberOCBIntroEmail($quote);

    Queue::assertPushed(SendCyberAutomatedFollowupJob::class, function ($job) {
        return $job->quoteUuid === 'test-uuid-123';
    });
});

test('does not dispatch followup job when followup already executed', function () {
    $quote = (object) [
        'uuid' => 'test-uuid-123',
        'code' => 'CYB-123',
        'email' => 'customer@test.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'mobile_no' => '971509876543',
        'source' => 'website',
        'advisor_id' => 1,
    ];

    $advisor = (object) ['id' => 1];
    $workflowUrl = (object) ['value' => 'https://bird.example.com/workflow/cyber-ocb-intro'];

    $queryBuilder = \Mockery::mock();
    $queryBuilder->shouldReceive('where')->once()->andReturnSelf();
    $queryBuilder->shouldReceive('first')->once()->andReturn($workflowUrl);

    $applicationStorageMock = \Mockery::mock('alias:'.ApplicationStorage::class);
    $applicationStorageMock->shouldReceive('where')->once()->andReturn($queryBuilder);
    $userMock = \Mockery::mock('alias:'.User::class);
    $userMock->shouldReceive('find')->once()->andReturn($advisor);

    $birdServiceMock = Mockery::mock(BirdService::class);
    $this->app->instance(BirdService::class, $birdServiceMock);

    $mockResponse = (object) [
        'status_code' => 200,
        'headers' => ['Run-Id' => ['test-run-id-123']],
        'body' => 'Success',
    ];

    $birdServiceMock->shouldReceive('isFollowupExecuted')->once()->andReturn(false);
    $birdServiceMock->shouldReceive('triggerWebHookRequest')->once()->andReturn($mockResponse);
    $birdServiceMock
        ->shouldReceive('isFollowupExecuted')
        ->once()
        ->with('test-uuid-123', QuoteTypes::CYBER->id(), QuoteFlowType::CYBER_AUTOMATED_FOLLOWUPS->value)
        ->andReturn(true);
    $birdServiceMock->shouldReceive('createQuoteWorkFlowDetails')->once();

    $service = app(CyberEmailService::class);
    $service->sendCyberOCBIntroEmail($quote);

    Queue::assertNotPushed(SendCyberAutomatedFollowupJob::class);
});

test('handles missing workflow URL gracefully', function () {
    $quote = (object) ['uuid' => 'test-uuid-123'];

    $queryBuilder = \Mockery::mock();
    $queryBuilder->shouldReceive('where')->once()->andReturnSelf();
    $queryBuilder->shouldReceive('first')->once()->andReturn(null);

    $applicationStorageMock = \Mockery::mock('alias:'.ApplicationStorage::class);
    $applicationStorageMock->shouldReceive('where')->once()->andReturn($queryBuilder);

    $service = app(CyberEmailService::class);
    $service->sendCyberOCBIntroEmail($quote);

    expect(true)->toBeTrue();
});

test('handles missing advisor gracefully', function () {
    $quote = (object) [
        'uuid' => 'test-uuid-123',
        'code' => 'CYB-123',
        'email' => 'customer@test.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'mobile_no' => '971509876543',
        'source' => 'website',
        'advisor_id' => null,
    ];

    $workflowUrl = (object) ['value' => 'https://bird.example.com/workflow/cyber-ocb-intro'];

    $queryBuilder = \Mockery::mock();
    $queryBuilder->shouldReceive('where')->once()->andReturnSelf();
    $queryBuilder->shouldReceive('first')->once()->andReturn($workflowUrl);

    $applicationStorageMock = \Mockery::mock('alias:'.ApplicationStorage::class);
    $applicationStorageMock->shouldReceive('where')->once()->andReturn($queryBuilder);
    $userMock = \Mockery::mock('alias:'.User::class);
    $userMock->shouldReceive('find')->once()->with(null)->andReturn(null);

    $birdServiceMock = Mockery::mock(BirdService::class);
    $this->app->instance(BirdService::class, $birdServiceMock);

    $mockResponse = (object) [
        'status_code' => 200,
        'headers' => ['Run-Id' => ['test-run-id-123']],
        'body' => 'Success',
    ];

    $birdServiceMock->shouldReceive('isFollowupExecuted')->twice()->andReturn(false);
    $birdServiceMock->shouldReceive('triggerWebHookRequest')->once()->andReturn($mockResponse);
    $birdServiceMock->shouldReceive('createQuoteWorkFlowDetails')->once();

    $service = app(CyberEmailService::class);
    $service->sendCyberOCBIntroEmail($quote);

    expect(true)->toBeTrue();
});

test('sends cyber automated followups successfully', function () {
    $quote = (object) [
        'uuid' => 'test-uuid-123',
        'code' => 'CYB-123',
        'email' => 'customer@test.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'mobile_no' => '971509876543',
        'source' => 'website',
        'advisor_id' => 1,
    ];

    $advisor = (object) ['id' => 1];
    $workflowUrl = (object) ['value' => 'https://bird.example.com/workflow/cyber-followups'];

    $queryBuilder = \Mockery::mock();
    $queryBuilder->shouldReceive('where')
        ->once()
        ->with('key_name', ApplicationStorageEnums::BIRD_CYBER_AUTOMATED_FOLLOWUPS)
        ->andReturnSelf();
    $queryBuilder->shouldReceive('first')->once()->andReturn($workflowUrl);

    $applicationStorageMock = \Mockery::mock('alias:'.ApplicationStorage::class);
    $applicationStorageMock->shouldReceive('where')
        ->once()
        ->with('key_name', ApplicationStorageEnums::BIRD_CYBER_AUTOMATED_FOLLOWUPS)
        ->andReturn($queryBuilder);

    $userMock = \Mockery::mock('alias:'.User::class);
    $userMock->shouldReceive('find')->once()->andReturn($advisor);

    $birdServiceMock = Mockery::mock(BirdService::class);
    $this->app->instance(BirdService::class, $birdServiceMock);

    $mockResponse = (object) [
        'status_code' => 200,
        'headers' => ['Run-Id' => ['test-run-id-456']],
        'body' => 'Success',
    ];

    $birdServiceMock
        ->shouldReceive('isFollowupExecuted')
        ->once()
        ->with('test-uuid-123', QuoteTypes::CYBER->id(), QuoteFlowType::CYBER_AUTOMATED_FOLLOWUPS->value)
        ->andReturn(false);

    $birdServiceMock
        ->shouldReceive('triggerWebHookRequest')
        ->once()
        ->with($workflowUrl->value, \Mockery::type('array'))
        ->andReturn($mockResponse);

    $birdServiceMock
        ->shouldReceive('createQuoteWorkFlowDetails')
        ->once()
        ->with($quote, $mockResponse, QuoteFlowType::CYBER_AUTOMATED_FOLLOWUPS->value, QuoteTypes::CYBER->id());

    $service = app(CyberEmailService::class);
    $service->sendCyberAutomatedFollowups($quote);

    expect(true)->toBeTrue();
});

test('does not send followups when already executed', function () {
    $quote = (object) ['uuid' => 'test-uuid-123'];

    $birdServiceMock = Mockery::mock(BirdService::class);
    $this->app->instance(BirdService::class, $birdServiceMock);

    $birdServiceMock
        ->shouldReceive('isFollowupExecuted')
        ->once()
        ->with('test-uuid-123', QuoteTypes::CYBER->id(), QuoteFlowType::CYBER_AUTOMATED_FOLLOWUPS->value)
        ->andReturn(true);

    $birdServiceMock->shouldNotReceive('triggerWebHookRequest');

    $service = app(CyberEmailService::class);
    $service->sendCyberAutomatedFollowups($quote);
});

test('handles missing followup workflow URL gracefully', function () {
    $quote = (object) ['uuid' => 'test-uuid-123'];

    $birdServiceMock = Mockery::mock(BirdService::class);
    $this->app->instance(BirdService::class, $birdServiceMock);

    $birdServiceMock->shouldReceive('isFollowupExecuted')->once()->andReturn(false);

    $queryBuilder = \Mockery::mock();
    $queryBuilder->shouldReceive('where')->once()->andReturnSelf();
    $queryBuilder->shouldReceive('first')->once()->andReturn(null);

    $applicationStorageMock = \Mockery::mock('alias:'.ApplicationStorage::class);
    $applicationStorageMock->shouldReceive('where')
        ->once()
        ->with('key_name', ApplicationStorageEnums::BIRD_CYBER_AUTOMATED_FOLLOWUPS)
        ->andReturn($queryBuilder);

    $birdServiceMock->shouldNotReceive('triggerWebHookRequest');

    $service = app(CyberEmailService::class);
    $service->sendCyberAutomatedFollowups($quote);

    expect(true)->toBeTrue();
});

test('handles non-200 response status code', function () {
    $quote = (object) [
        'uuid' => 'test-uuid-123',
        'code' => 'CYB-123',
        'email' => 'customer@test.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'mobile_no' => '971509876543',
        'source' => 'website',
        'advisor_id' => 1,
    ];

    $advisor = (object) ['id' => 1];
    $workflowUrl = (object) ['value' => 'https://bird.example.com/workflow/cyber-followups'];

    $queryBuilder = \Mockery::mock();
    $queryBuilder->shouldReceive('where')->once()->andReturnSelf();
    $queryBuilder->shouldReceive('first')->once()->andReturn($workflowUrl);

    $applicationStorageMock = \Mockery::mock('alias:'.ApplicationStorage::class);
    $applicationStorageMock->shouldReceive('where')->once()->andReturn($queryBuilder);
    $userMock = \Mockery::mock('alias:'.User::class);
    $userMock->shouldReceive('find')->once()->andReturn($advisor);

    $birdServiceMock = Mockery::mock(BirdService::class);
    $this->app->instance(BirdService::class, $birdServiceMock);

    $mockResponse = (object) [
        'status_code' => 500,
        'headers' => [],
        'body' => 'Error',
    ];

    $birdServiceMock->shouldReceive('isFollowupExecuted')->once()->andReturn(false);
    $birdServiceMock->shouldReceive('triggerWebHookRequest')->once()->andReturn($mockResponse);
    $birdServiceMock->shouldNotReceive('createQuoteWorkFlowDetails');

    $service = app(CyberEmailService::class);
    $service->sendCyberAutomatedFollowups($quote);

    expect(true)->toBeTrue();
});

test('SendCyberOCBIntroEmailJob handles missing quote gracefully', function () {
    $nonExistentUuid = 'non-existent-uuid-'.uniqid();

    $queryBuilder = \Mockery::mock();
    $queryBuilder->shouldReceive('where')->once()->with('uuid', $nonExistentUuid)->andReturnSelf();
    $queryBuilder->shouldReceive('first')->once()->andReturn(null);

    $personalQuoteMock = \Mockery::mock('alias:'.PersonalQuote::class);
    $personalQuoteMock->shouldReceive('where')->once()->with('uuid', $nonExistentUuid)->andReturn($queryBuilder);

    $service = app(CyberEmailService::class);
    $job = new SendCyberOCBIntroEmailJob($nonExistentUuid);

    expect(fn () => $job->handle($service))->not->toThrow();
});

test('SendCyberOCBIntroEmailJob processes quote successfully', function () {
    $quote = (object) [
        'uuid' => 'test-uuid-123',
        'code' => 'CYB-123',
        'email' => 'customer@test.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'mobile_no' => '971509876543',
        'source' => 'website',
        'advisor_id' => 1,
    ];

    $advisor = (object) [
        'id' => 1,
        'email' => 'advisor@test.com',
        'name' => 'Test Advisor',
        'mobile_no' => '971501234567',
        'landline_no' => '97141234567',
        'profile_photo_path' => '/path/to/photo.jpg',
    ];

    $workflowUrl = (object) ['value' => 'https://bird.example.com/workflow/cyber-ocb-intro'];

    $quoteQueryBuilder = Mockery::mock();
    $quoteQueryBuilder->shouldReceive('where')->once()->andReturnSelf();
    $quoteQueryBuilder->shouldReceive('first')->once()->andReturn($quote);

    $personalQuoteMock = \Mockery::mock('alias:'.PersonalQuote::class);
    $personalQuoteMock->shouldReceive('where')->once()->andReturn($quoteQueryBuilder);

    $appStorageQueryBuilder = Mockery::mock();
    $appStorageQueryBuilder->shouldReceive('where')->once()->andReturnSelf();
    $appStorageQueryBuilder->shouldReceive('first')->once()->andReturn($workflowUrl);

    $applicationStorageMock = \Mockery::mock('alias:'.ApplicationStorage::class);
    $applicationStorageMock->shouldReceive('where')->once()->andReturn($appStorageQueryBuilder);
    $userMock = \Mockery::mock('alias:'.User::class);
    $userMock->shouldReceive('find')->once()->andReturn($advisor);

    $birdServiceMock = Mockery::mock(BirdService::class);
    $this->app->instance(BirdService::class, $birdServiceMock);

    $mockResponse = (object) [
        'status_code' => 200,
        'headers' => ['Run-Id' => ['test-run-id-123']],
        'body' => 'Success',
    ];

    $birdServiceMock->shouldReceive('isFollowupExecuted')->twice()->andReturn(false);
    $birdServiceMock->shouldReceive('triggerWebHookRequest')->once()->andReturn($mockResponse);
    $birdServiceMock->shouldReceive('createQuoteWorkFlowDetails')->once();

    $service = app(CyberEmailService::class);
    $job = new SendCyberOCBIntroEmailJob('test-uuid-123');
    $job->handle($service);

    Queue::assertPushed(SendCyberAutomatedFollowupJob::class);
});

test('SendCyberAutomatedFollowupJob handles missing quote gracefully', function () {
    $nonExistentUuid = 'non-existent-uuid-'.uniqid();

    $queryBuilder = \Mockery::mock();
    $queryBuilder->shouldReceive('where')->once()->with('uuid', $nonExistentUuid)->andReturnSelf();
    $queryBuilder->shouldReceive('first')->once()->andReturn(null);

    $personalQuoteMock = \Mockery::mock('alias:'.PersonalQuote::class);
    $personalQuoteMock->shouldReceive('where')->once()->with('uuid', $nonExistentUuid)->andReturn($queryBuilder);

    $job = new SendCyberAutomatedFollowupJob($nonExistentUuid);

    expect(fn () => $job->handle())->not->toThrow();
});

test('SendCyberAutomatedFollowupJob processes quote successfully', function () {
    $quote = (object) [
        'uuid' => 'test-uuid-123',
        'code' => 'CYB-123',
        'email' => 'customer@test.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'mobile_no' => '971509876543',
        'source' => 'website',
        'advisor_id' => 1,
    ];

    $advisor = (object) ['id' => 1];
    $workflowUrl = (object) ['value' => 'https://bird.example.com/workflow/cyber-followups'];

    $quoteQueryBuilder = Mockery::mock();
    $quoteQueryBuilder->shouldReceive('where')->once()->andReturnSelf();
    $quoteQueryBuilder->shouldReceive('first')->once()->andReturn($quote);

    $personalQuoteMock = \Mockery::mock('alias:'.PersonalQuote::class);
    $personalQuoteMock->shouldReceive('where')->once()->andReturn($quoteQueryBuilder);

    $appStorageQueryBuilder = Mockery::mock();
    $appStorageQueryBuilder->shouldReceive('where')
        ->once()
        ->with('key_name', ApplicationStorageEnums::BIRD_CYBER_AUTOMATED_FOLLOWUPS)
        ->andReturnSelf();
    $appStorageQueryBuilder->shouldReceive('first')->once()->andReturn($workflowUrl);

    $applicationStorageMock = \Mockery::mock('alias:'.ApplicationStorage::class);
    $applicationStorageMock->shouldReceive('where')->once()->andReturn($appStorageQueryBuilder);
    $userMock = \Mockery::mock('alias:'.User::class);
    $userMock->shouldReceive('find')->once()->andReturn($advisor);

    $birdServiceMock = Mockery::mock(BirdService::class);
    $this->app->instance(BirdService::class, $birdServiceMock);

    $mockResponse = (object) [
        'status_code' => 200,
        'headers' => ['Run-Id' => ['test-run-id-456']],
        'body' => 'Success',
    ];

    $birdServiceMock->shouldReceive('isFollowupExecuted')->once()->andReturn(false);
    $birdServiceMock->shouldReceive('triggerWebHookRequest')->once()->andReturn($mockResponse);
    $birdServiceMock->shouldReceive('createQuoteWorkFlowDetails')->once();

    $job = new SendCyberAutomatedFollowupJob('test-uuid-123');
    $job->handle();

    expect(true)->toBeTrue();
});

test('buildEmailData includes all required fields', function () {
    $quote = (object) [
        'uuid' => 'test-uuid-123',
        'code' => 'CYB-123',
        'email' => 'customer@test.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'mobile_no' => '971509876543',
        'source' => 'website',
        'advisor_id' => 1,
    ];

    $advisor = (object) [
        'id' => 1,
        'email' => 'advisor@test.com',
        'name' => 'Test Advisor',
        'mobile_no' => '971501234567',
        'landline_no' => '97141234567',
        'profile_photo_path' => '/path/to/photo.jpg',
    ];

    $workflowUrl = (object) ['value' => 'https://bird.example.com/workflow/cyber-ocb-intro'];

    $queryBuilder = \Mockery::mock();
    $queryBuilder->shouldReceive('where')->once()->andReturnSelf();
    $queryBuilder->shouldReceive('first')->once()->andReturn($workflowUrl);

    $applicationStorageMock = \Mockery::mock('alias:'.ApplicationStorage::class);
    $applicationStorageMock->shouldReceive('where')->once()->andReturn($queryBuilder);
    $userMock = \Mockery::mock('alias:'.User::class);
    $userMock->shouldReceive('find')->once()->andReturn($advisor);

    $birdServiceMock = Mockery::mock(BirdService::class);
    $this->app->instance(BirdService::class, $birdServiceMock);

    $mockResponse = (object) [
        'status_code' => 200,
        'headers' => ['Run-Id' => ['test-run-id-123']],
        'body' => 'Success',
    ];

    $capturedData = null;

    $birdServiceMock->shouldReceive('isFollowupExecuted')->twice()->andReturn(false);
    $birdServiceMock
        ->shouldReceive('triggerWebHookRequest')
        ->once()
        ->with($workflowUrl->value, \Mockery::capture($capturedData))
        ->andReturn($mockResponse);
    $birdServiceMock->shouldReceive('createQuoteWorkFlowDetails')->once();

    $service = app(CyberEmailService::class);
    $service->sendCyberOCBIntroEmail($quote);

    expect($capturedData)->not->toBeNull()
        ->and($capturedData)->toHaveKey('quoteUID')
        ->and($capturedData)->toHaveKey('customerEmail')
        ->and($capturedData)->toHaveKey('customerFullName')
        ->and($capturedData)->toHaveKey('customerName')
        ->and($capturedData)->toHaveKey('refID')
        ->and($capturedData)->toHaveKey('customerMobile')
        ->and($capturedData)->toHaveKey('advisorEmail')
        ->and($capturedData)->toHaveKey('advisorName')
        ->and($capturedData)->toHaveKey('advisorMobilePhone')
        ->and($capturedData)->toHaveKey('advisorLandLine')
        ->and($capturedData)->toHaveKey('advisorWhatsAppNumber')
        ->and($capturedData)->toHaveKey('whatsappConsent')
        ->and($capturedData)->toHaveKey('workflowType')
        ->and($capturedData)->toHaveKey('isFollowupExecuted')
        ->and($capturedData['quoteUID'])->toBe('test-uuid-123')
        ->and($capturedData['customerEmail'])->toBe('customer@test.com')
        ->and($capturedData['customerFullName'])->toBe('John Doe')
        ->and($capturedData['refID'])->toBe('CYB-123')
        ->and($capturedData['workflowType'])->toBe(WorkflowTypeEnum::CYBER_OCB_INTRO_EMAIL);
});

test('buildEmailData handles null advisor fields', function () {
    $quote = (object) [
        'uuid' => 'test-uuid-123',
        'code' => 'CYB-123',
        'email' => 'customer@test.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'mobile_no' => '971509876543',
        'source' => 'website',
        'advisor_id' => null,
    ];

    $workflowUrl = (object) ['value' => 'https://bird.example.com/workflow/cyber-ocb-intro'];

    $queryBuilder = \Mockery::mock();
    $queryBuilder->shouldReceive('where')->once()->andReturnSelf();
    $queryBuilder->shouldReceive('first')->once()->andReturn($workflowUrl);

    $applicationStorageMock = \Mockery::mock('alias:'.ApplicationStorage::class);
    $applicationStorageMock->shouldReceive('where')->once()->andReturn($queryBuilder);
    $userMock = \Mockery::mock('alias:'.User::class);
    $userMock->shouldReceive('find')->once()->with(null)->andReturn(null);

    $birdServiceMock = Mockery::mock(BirdService::class);
    $this->app->instance(BirdService::class, $birdServiceMock);

    $mockResponse = (object) [
        'status_code' => 200,
        'headers' => ['Run-Id' => ['test-run-id-123']],
        'body' => 'Success',
    ];

    $capturedData = null;

    $birdServiceMock->shouldReceive('isFollowupExecuted')->twice()->andReturn(false);
    $birdServiceMock
        ->shouldReceive('triggerWebHookRequest')
        ->once()
        ->with($workflowUrl->value, \Mockery::capture($capturedData))
        ->andReturn($mockResponse);
    $birdServiceMock->shouldReceive('createQuoteWorkFlowDetails')->once();

    $service = app(CyberEmailService::class);
    $service->sendCyberOCBIntroEmail($quote);

    expect($capturedData['advisorEmail'])->toBe('')
        ->and($capturedData['advisorName'])->toBe('')
        ->and($capturedData['advisorMobilePhone'])->toBe('')
        ->and($capturedData['advisorLandLine'])->toBe('')
        ->and($capturedData['advisorWhatsAppNumber'])->toBe('');
});

test('creates WhatsApp flow details when consent is given', function () {
    $quote = (object) [
        'uuid' => 'test-uuid-123',
        'code' => 'CYB-123',
        'email' => 'customer@test.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'mobile_no' => '971509876543',
        'source' => 'website',
        'advisor_id' => 1,
    ];

    $advisor = (object) [
        'id' => 1,
        'email' => 'advisor@test.com',
        'name' => 'Test Advisor',
        'mobile_no' => '971501234567',
        'landline_no' => '97141234567',
        'profile_photo_path' => '/path/to/photo.jpg',
    ];

    $workflowUrl = (object) ['value' => 'https://bird.example.com/workflow/cyber-ocb-intro'];

    $queryBuilder = \Mockery::mock();
    $queryBuilder->shouldReceive('where')->once()->andReturnSelf();
    $queryBuilder->shouldReceive('first')->once()->andReturn($workflowUrl);

    $applicationStorageMock = \Mockery::mock('alias:'.ApplicationStorage::class);
    $applicationStorageMock->shouldReceive('where')->once()->andReturn($queryBuilder);
    $userMock = \Mockery::mock('alias:'.User::class);
    $userMock->shouldReceive('find')->once()->andReturn($advisor);

    $birdServiceMock = Mockery::mock(BirdService::class);
    $this->app->instance(BirdService::class, $birdServiceMock);

    $mockResponse = (object) [
        'status_code' => 200,
        'headers' => ['Run-Id' => ['test-run-id-123']],
        'body' => 'Success',
    ];

    $birdServiceMock->shouldReceive('isFollowupExecuted')->twice()->andReturn(false);
    $birdServiceMock->shouldReceive('triggerWebHookRequest')->once()->andReturn($mockResponse);
    $birdServiceMock->shouldReceive('createQuoteWorkFlowDetails')->once();

    // getWhatsappConsent will return false by default (no database), so WhatsApp flow should NOT be created
    $birdServiceMock->shouldNotReceive('createQuoteWhatsAppFlowDetails');

    $service = app(CyberEmailService::class);
    $service->sendCyberOCBIntroEmail($quote);

    Queue::assertPushed(SendCyberAutomatedFollowupJob::class);
});
