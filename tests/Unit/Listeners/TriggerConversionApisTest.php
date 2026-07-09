<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypeId;
use App\Events\QuotePolicyBooked;
use App\Listeners\TriggerConversionApis;
use App\Services\ConversionApiService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class TriggerConversionApisTest extends TestCase
{
    private ConversionApiService $mockConversionApiService;
    private TriggerConversionApis $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConversionApiService = Mockery::mock(ConversionApiService::class);
        $this->listener = new TriggerConversionApis($this->mockConversionApiService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_calls_both_facebook_and_google_conversion_apis(): void
    {
        $quoteUID = 'test-quote-uuid-123';
        $quoteTypeId = QuoteTypeId::Car;
        $eventType = 'Purchase';

        $event = new QuotePolicyBooked($quoteUID, $quoteTypeId, $eventType);

        $this->mockConversionApiService
            ->shouldReceive('triggerFacebookConversion')
            ->once()
            ->with($quoteUID, $quoteTypeId, $eventType)
            ->andReturn(true);

        $this->mockConversionApiService
            ->shouldReceive('triggerGoogleConversion')
            ->once()
            ->with($quoteUID, $quoteTypeId, $eventType)
            ->andReturn(true);

        $this->listener->handle($event);

        $this->addToAssertionCount(2);
    }

    public function test_handle_logs_processing_start(): void
    {
        Log::spy();

        $quoteUID = 'test-quote-uuid-456';
        $quoteTypeId = QuoteTypeId::Travel;

        $event = new QuotePolicyBooked($quoteUID, $quoteTypeId);

        $this->mockConversionApiService
            ->shouldReceive('triggerFacebookConversion')
            ->andReturn(true);

        $this->mockConversionApiService
            ->shouldReceive('triggerGoogleConversion')
            ->andReturn(true);

        $this->listener->handle($event);

        Log::shouldHaveReceived('info')
            ->with('TriggerConversionApis - Processing conversion APIs for PolicyBooked quote', Mockery::on(function ($context) use ($quoteTypeId) {
                return isset($context['quoteTypeId']) && $context['quoteTypeId'] === $quoteTypeId
                    && isset($context['eventType']) && $context['eventType'] === 'Purchase';
            }))
            ->once();

        $this->addToAssertionCount(1);
    }

    public function test_handle_logs_processing_completion_with_success_status(): void
    {
        Log::spy();

        $quoteUID = 'test-quote-uuid-789';
        $quoteTypeId = QuoteTypeId::Health;

        $event = new QuotePolicyBooked($quoteUID, $quoteTypeId);

        $this->mockConversionApiService
            ->shouldReceive('triggerFacebookConversion')
            ->andReturn(true);

        $this->mockConversionApiService
            ->shouldReceive('triggerGoogleConversion')
            ->andReturn(true);

        $this->listener->handle($event);

        Log::shouldHaveReceived('info')
            ->with('TriggerConversionApis - Conversion APIs processing completed', Mockery::on(function ($context) use ($quoteTypeId) {
                return isset($context['quoteTypeId']) && $context['quoteTypeId'] === $quoteTypeId
                    && isset($context['eventType']) && $context['eventType'] === 'Purchase'
                    && isset($context['facebookSuccess']) && $context['facebookSuccess'] === true
                    && isset($context['googleSuccess']) && $context['googleSuccess'] === true;
            }))
            ->once();

        $this->addToAssertionCount(1);
    }

    public function test_handle_logs_processing_completion_with_failure_status(): void
    {
        Log::spy();

        $quoteUID = 'test-quote-uuid-fail';
        $quoteTypeId = QuoteTypeId::Car;

        $event = new QuotePolicyBooked($quoteUID, $quoteTypeId);

        $this->mockConversionApiService
            ->shouldReceive('triggerFacebookConversion')
            ->andReturn(false);

        $this->mockConversionApiService
            ->shouldReceive('triggerGoogleConversion')
            ->andReturn(false);

        $this->listener->handle($event);

        Log::shouldHaveReceived('info')
            ->with('TriggerConversionApis - Conversion APIs processing completed', Mockery::on(function ($context) {
                return isset($context['facebookSuccess']) && $context['facebookSuccess'] === false
                    && isset($context['googleSuccess']) && $context['googleSuccess'] === false;
            }))
            ->once();

        $this->addToAssertionCount(1);
    }

    public function test_handle_handles_exceptions_gracefully(): void
    {
        Log::spy();

        $quoteUID = 'test-quote-uuid-exception';
        $quoteTypeId = QuoteTypeId::Travel;

        $event = new QuotePolicyBooked($quoteUID, $quoteTypeId);

        $this->mockConversionApiService
            ->shouldReceive('triggerFacebookConversion')
            ->andThrow(new \Exception('API Error'));

        $this->listener->handle($event);

        Log::shouldHaveReceived('error')
            ->with('TriggerConversionApis - Exception occurred while processing conversion APIs', Mockery::on(function ($context) {
                return isset($context['exception']) && is_array($context['exception'])
                    && isset($context['exception']['message'])
                    && isset($context['exception']['trace'])
                    && isset($context['exception']['code'])
                    && isset($context['quoteTypeId'])
                    && isset($context['eventType'])
                    && isset($context['error']);
            }))
            ->once();

        $this->addToAssertionCount(1);
    }

    public function test_handle_works_with_custom_event_type(): void
    {
        $quoteUID = 'test-quote-uuid-custom';
        $quoteTypeId = QuoteTypeId::Business;
        $eventType = 'CustomEvent';

        $event = new QuotePolicyBooked($quoteUID, $quoteTypeId, $eventType);

        $this->mockConversionApiService
            ->shouldReceive('triggerFacebookConversion')
            ->once()
            ->with($quoteUID, $quoteTypeId, $eventType)
            ->andReturn(true);

        $this->mockConversionApiService
            ->shouldReceive('triggerGoogleConversion')
            ->once()
            ->with($quoteUID, $quoteTypeId, $eventType)
            ->andReturn(true);

        $this->listener->handle($event);

        $this->addToAssertionCount(2);
    }

    public function test_handle_continues_even_if_one_api_fails(): void
    {
        $quoteUID = 'test-quote-uuid-partial';
        $quoteTypeId = QuoteTypeId::Car;

        $event = new QuotePolicyBooked($quoteUID, $quoteTypeId);

        $this->mockConversionApiService
            ->shouldReceive('triggerFacebookConversion')
            ->once()
            ->andReturn(false);

        $this->mockConversionApiService
            ->shouldReceive('triggerGoogleConversion')
            ->once()
            ->andReturn(true);

        $this->listener->handle($event);

        $this->addToAssertionCount(1);
    }

    public function test_handle_works_with_dynamic_quote_type_ids(): void
    {
        $testCases = [
            ['quoteUID' => 'NREFC7RS', 'quoteTypeId' => 19],
            ['quoteUID' => '6UTE2JXU', 'quoteTypeId' => 1],
            ['quoteUID' => 'TEST123', 'quoteTypeId' => 5],
            ['quoteUID' => 'XYZ789', 'quoteTypeId' => 25],
        ];

        foreach ($testCases as $testCase) {
            $event = new QuotePolicyBooked($testCase['quoteUID'], $testCase['quoteTypeId']);

            $this->mockConversionApiService
                ->shouldReceive('triggerFacebookConversion')
                ->once()
                ->with($testCase['quoteUID'], $testCase['quoteTypeId'], 'Purchase')
                ->andReturn(true);

            $this->mockConversionApiService
                ->shouldReceive('triggerGoogleConversion')
                ->once()
                ->with($testCase['quoteUID'], $testCase['quoteTypeId'], 'Purchase')
                ->andReturn(true);

            $this->listener->handle($event);
        }

        $this->addToAssertionCount(count($testCases) * 2);
    }

    public function test_handle_skips_google_conversion_for_renewal_upload_lead_source(): void
    {
        $quoteUID = 'test-quote-uuid-renewal';
        $quoteTypeId = QuoteTypeId::Car;

        $event = new QuotePolicyBooked($quoteUID, $quoteTypeId, leadSource: LeadSourceEnum::RENEWAL_UPLOAD);

        $this->mockConversionApiService
            ->shouldReceive('triggerFacebookConversion')
            ->once()
            ->with($quoteUID, $quoteTypeId, 'Purchase')
            ->andReturn(true);

        $this->mockConversionApiService
            ->shouldNotReceive('triggerGoogleConversion');

        $this->listener->handle($event);

        $this->addToAssertionCount(2);
    }

    public function test_handle_calls_google_conversion_for_non_renewal_upload_lead_source(): void
    {
        $quoteUID = 'test-quote-uuid-non-renewal';
        $quoteTypeId = QuoteTypeId::Car;

        $event = new QuotePolicyBooked($quoteUID, $quoteTypeId, leadSource: 'Website');

        $this->mockConversionApiService
            ->shouldReceive('triggerFacebookConversion')
            ->once()
            ->with($quoteUID, $quoteTypeId, 'Purchase')
            ->andReturn(true);

        $this->mockConversionApiService
            ->shouldReceive('triggerGoogleConversion')
            ->once()
            ->with($quoteUID, $quoteTypeId, 'Purchase')
            ->andReturn(true);

        $this->listener->handle($event);

        $this->addToAssertionCount(2);
    }

    public function test_handle_passes_exact_parameters_from_event(): void
    {
        $quoteUID = 'NREFC7RS';
        $quoteTypeId = 19;
        $eventType = 'Purchase';

        $event = new QuotePolicyBooked($quoteUID, $quoteTypeId, $eventType);

        // Verify exact parameters are passed through
        $this->mockConversionApiService
            ->shouldReceive('triggerFacebookConversion')
            ->once()
            ->with(
                Mockery::on(function ($uid) use ($quoteUID) {
                    return $uid === $quoteUID;
                }),
                Mockery::on(function ($typeId) use ($quoteTypeId) {
                    return $typeId === $quoteTypeId && is_int($typeId);
                }),
                Mockery::on(function ($type) use ($eventType) {
                    return $type === $eventType;
                })
            )
            ->andReturn(true);

        $this->mockConversionApiService
            ->shouldReceive('triggerGoogleConversion')
            ->once()
            ->with(
                Mockery::on(function ($uid) use ($quoteUID) {
                    return $uid === $quoteUID;
                }),
                Mockery::on(function ($typeId) use ($quoteTypeId) {
                    return $typeId === $quoteTypeId && is_int($typeId);
                }),
                Mockery::on(function ($type) use ($eventType) {
                    return $type === $eventType;
                })
            )
            ->andReturn(true);

        $this->listener->handle($event);

        $this->addToAssertionCount(2);
    }
}
