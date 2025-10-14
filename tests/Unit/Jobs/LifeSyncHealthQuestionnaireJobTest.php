<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\LifeSyncHealthQuestionnaireJob;
use App\Services\MetLife\MetLifeApiService;
use Exception;
use Mockery;
use Tests\TestCase;

class LifeSyncHealthQuestionnaireJobTest extends TestCase
{
    private LifeSyncHealthQuestionnaireJob $job;
    private $mockMetLifeApiService;

    protected function setUp(): void
    {
        parent::setUp();

        $requestData = [
            'quote_uuid' => 'test-quote-uuid-123',
            'policy_number' => 'POL123456',
        ];

        $this->job = new LifeSyncHealthQuestionnaireJob($requestData);
        $this->mockMetLifeApiService = Mockery::mock(MetLifeApiService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_job_constructor_sets_properties()
    {
        $requestData = [
            'quote_uuid' => 'test-quote-uuid-456',
            'policy_number' => 'POL789012',
        ];

        $job = new LifeSyncHealthQuestionnaireJob($requestData);

        $reflection = new \ReflectionClass($job);
        $requestDataProperty = $reflection->getProperty('requestData');
        $requestDataProperty->setAccessible(true);

        $this->assertEquals($requestData, $requestDataProperty->getValue($job));
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(120, $job->timeout);
        $this->assertEquals(300, $job->backoff);
    }

    public function test_handle_success()
    {
        $requestData = [
            'quote_uuid' => 'test-quote-uuid-123',
            'policy_number' => 'POL123456',
        ];

        $job = new LifeSyncHealthQuestionnaireJob($requestData);

        $mockResult = [
            'success' => true,
            'message' => 'Health questionnaire synced successfully',
            'data' => ['document_id' => 123],
        ];

        $this->mockMetLifeApiService->shouldReceive('syncHealthQuestionnaire')
            ->with($requestData)
            ->andReturn($mockResult);

        // Should not throw any exception
        $job->handle($this->mockMetLifeApiService);

        $this->assertTrue(true); // If we reach here, no exception was thrown
    }

    public function test_handle_with_error_response()
    {
        $requestData = [
            'quote_uuid' => 'test-quote-uuid-123',
            'policy_number' => 'POL123456',
        ];

        $job = new LifeSyncHealthQuestionnaireJob($requestData);

        $mockResult = [
            'success' => false,
            'message' => 'Sync failed',
            'data' => [],
        ];

        $this->mockMetLifeApiService->shouldReceive('syncHealthQuestionnaire')
            ->with($requestData)
            ->andReturn($mockResult);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Health questionnaire sync failed: Sync failed');

        $job->handle($this->mockMetLifeApiService);
    }

    public function test_handle_with_exception()
    {
        $requestData = [
            'quote_uuid' => 'test-quote-uuid-123',
            'policy_number' => 'POL123456',
        ];

        $job = new LifeSyncHealthQuestionnaireJob($requestData);

        $this->mockMetLifeApiService->shouldReceive('syncHealthQuestionnaire')
            ->with($requestData)
            ->andThrow(new Exception('API Error'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('API Error');

        $job->handle($this->mockMetLifeApiService);
    }

    public function test_handle_with_array_result_success_true()
    {
        $requestData = [
            'quote_uuid' => 'test-quote-uuid-123',
            'policy_number' => 'POL123456',
        ];

        $job = new LifeSyncHealthQuestionnaireJob($requestData);

        $mockResult = [
            'success' => true,
            'message' => 'Success',
            'data' => ['document_id' => 123],
        ];

        $this->mockMetLifeApiService->shouldReceive('syncHealthQuestionnaire')
            ->with($requestData)
            ->andReturn($mockResult);

        // Should not throw any exception since success is true
        $job->handle($this->mockMetLifeApiService);

        $this->assertTrue(true); // If we reach here, no exception was thrown
    }

    public function test_middleware_returns_array()
    {
        $requestData = [
            'quote_uuid' => 'test-quote-uuid-123',
            'policy_number' => 'POL123456',
        ];

        $job = new LifeSyncHealthQuestionnaireJob($requestData);
        $middleware = $job->middleware();

        $this->assertIsArray($middleware);
    }

    public function test_job_implements_should_queue()
    {
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $this->job);
    }

    public function test_job_uses_correct_traits()
    {
        $reflection = new \ReflectionClass($this->job);
        $traits = $reflection->getTraitNames();

        $this->assertContains(\Illuminate\Bus\Queueable::class, $traits);
        $this->assertContains(\Illuminate\Queue\InteractsWithQueue::class, $traits);
        $this->assertContains(\Illuminate\Queue\SerializesModels::class, $traits);
        $this->assertContains(\Illuminate\Foundation\Bus\Dispatchable::class, $traits);
    }

    public function test_job_properties_are_correctly_typed()
    {
        $this->assertIsInt($this->job->tries);
        $this->assertIsInt($this->job->timeout);
        $this->assertIsInt($this->job->backoff);

        $this->assertEquals(3, $this->job->tries);
        $this->assertEquals(120, $this->job->timeout);
        $this->assertEquals(300, $this->job->backoff);
    }

    public function test_job_has_required_methods()
    {
        $reflection = new \ReflectionClass($this->job);

        $this->assertTrue($reflection->hasMethod('handle'));
        $this->assertTrue($reflection->hasMethod('failed'));
        $this->assertTrue($reflection->hasMethod('middleware'));
    }

    public function test_job_methods_are_public()
    {
        $reflection = new \ReflectionClass($this->job);

        $handleMethod = $reflection->getMethod('handle');
        $this->assertTrue($handleMethod->isPublic());

        $failedMethod = $reflection->getMethod('failed');
        $this->assertTrue($failedMethod->isPublic());

        $middlewareMethod = $reflection->getMethod('middleware');
        $this->assertTrue($middlewareMethod->isPublic());
    }

    public function test_job_constructor_accepts_array()
    {
        $requestData = [
            'quote_uuid' => 'test-uuid',
            'policy_number' => 'POL123',
            'additional_field' => 'value',
        ];

        $job = new LifeSyncHealthQuestionnaireJob($requestData);

        $reflection = new \ReflectionClass($job);
        $requestDataProperty = $reflection->getProperty('requestData');
        $requestDataProperty->setAccessible(true);

        $this->assertEquals($requestData, $requestDataProperty->getValue($job));
    }

    public function test_job_constructor_with_empty_array()
    {
        $requestData = [];

        $job = new LifeSyncHealthQuestionnaireJob($requestData);

        $reflection = new \ReflectionClass($job);
        $requestDataProperty = $reflection->getProperty('requestData');
        $requestDataProperty->setAccessible(true);

        $this->assertEquals($requestData, $requestDataProperty->getValue($job));
    }

    public function test_job_constructor_with_minimal_data()
    {
        $requestData = [
            'quote_uuid' => 'test-uuid',
        ];

        $job = new LifeSyncHealthQuestionnaireJob($requestData);

        $reflection = new \ReflectionClass($job);
        $requestDataProperty = $reflection->getProperty('requestData');
        $requestDataProperty->setAccessible(true);

        $this->assertEquals($requestData, $requestDataProperty->getValue($job));
    }

}
