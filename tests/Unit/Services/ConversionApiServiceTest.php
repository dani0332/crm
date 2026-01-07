<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\QuoteTypeId;
use App\Facades\Capi;
use App\Services\ConversionApiService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class ConversionApiServiceTest extends TestCase
{
    private ConversionApiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new ConversionApiService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_trigger_facebook_conversion_success(): void
    {
        $mockCapi = Mockery::mock('alias:'.Capi::class);
        $mockCapi->shouldReceive('request')
            ->once()
            ->with('/api/v1-trigger-facebook-event-conversion', 'post', Mockery::on(function ($payload) {
                return $payload['quoteUID'] === 'test-uuid-123'
                    && $payload['quoteTypeId'] === QuoteTypeId::Car
                    && $payload['eventType'] === 'Purchase';
            }))
            ->andReturn((object) ['success' => true]);

        $result = $this->service->triggerFacebookConversion('test-uuid-123', QuoteTypeId::Car);

        $this->assertTrue($result);
    }

    public function test_trigger_google_conversion_success(): void
    {
        $mockCapi = Mockery::mock('alias:'.Capi::class);
        $mockCapi->shouldReceive('request')
            ->once()
            ->with('/api/v1-trigger-google-event-conversion', 'post', Mockery::on(function ($payload) {
                return $payload['quoteUID'] === 'test-uuid-456'
                    && $payload['quoteTypeId'] === QuoteTypeId::Travel
                    && $payload['eventType'] === 'Purchase';
            }))
            ->andReturn((object) ['success' => true]);

        $result = $this->service->triggerGoogleConversion('test-uuid-456', QuoteTypeId::Travel);

        $this->assertTrue($result);
    }

    public function test_trigger_facebook_conversion_with_custom_event_type(): void
    {
        $mockCapi = Mockery::mock('alias:'.Capi::class);
        $mockCapi->shouldReceive('request')
            ->once()
            ->with('/api/v1-trigger-facebook-event-conversion', 'post', Mockery::on(function ($payload) {
                return $payload['eventType'] === 'CustomEvent';
            }))
            ->andReturn((object) ['success' => true]);

        $result = $this->service->triggerFacebookConversion('test-uuid-789', QuoteTypeId::Health, 'CustomEvent');

        $this->assertTrue($result);
    }

    public function test_trigger_facebook_conversion_handles_exception(): void
    {
        $mockCapi = Mockery::mock('alias:'.Capi::class);
        $mockCapi->shouldReceive('request')
            ->once()
            ->andThrow(new \Exception('API Error'));

        $result = $this->service->triggerFacebookConversion('test-uuid-error', QuoteTypeId::Car);

        $this->assertFalse($result);
    }

    public function test_trigger_google_conversion_handles_exception(): void
    {
        $mockCapi = Mockery::mock('alias:'.Capi::class);
        $mockCapi->shouldReceive('request')
            ->once()
            ->andThrow(new \Exception('API Error'));

        $result = $this->service->triggerGoogleConversion('test-uuid-error', QuoteTypeId::Car);

        $this->assertFalse($result);
    }

    public function test_trigger_facebook_conversion_logs_request(): void
    {
        Log::spy();
        
        $quoteUID = 'test-uuid-log';
        $quoteTypeId = QuoteTypeId::Car;
        
        $mockCapi = Mockery::mock('alias:'.Capi::class);
        $mockCapi->shouldReceive('request')
            ->andReturn((object) ['success' => true]);
        
        $result = $this->service->triggerFacebookConversion($quoteUID, $quoteTypeId);

        Log::shouldHaveReceived('info')
            ->with(\Mockery::pattern('/ConversionApiService - Calling facebook conversion API/'), \Mockery::on(function ($context) use ($quoteUID, $quoteTypeId) {
                return isset($context['uuid']) && $context['uuid'] === $quoteUID
                    && isset($context['eventType']) && $context['eventType'] === 'Purchase'
                    && isset($context['platform']) && $context['platform'] === 'facebook'
                    && isset($context['quoteTypeId']) && $context['quoteTypeId'] === $quoteTypeId;
            }))
            ->once();
        
        $this->assertTrue($result);
    }

    public function test_trigger_google_conversion_logs_request(): void
    {
        Log::spy();
        
        $quoteUID = 'test-uuid-log-google';
        $quoteTypeId = QuoteTypeId::Travel;
        
        $mockCapi = Mockery::mock('alias:'.Capi::class);
        $mockCapi->shouldReceive('request')
            ->andReturn((object) ['success' => true]);
        
        $result = $this->service->triggerGoogleConversion($quoteUID, $quoteTypeId);

        Log::shouldHaveReceived('info')
            ->with(\Mockery::pattern('/ConversionApiService - Calling google conversion API/'), \Mockery::on(function ($context) use ($quoteUID, $quoteTypeId) {
                return isset($context['uuid']) && $context['uuid'] === $quoteUID
                    && isset($context['eventType']) && $context['eventType'] === 'Purchase'
                    && isset($context['platform']) && $context['platform'] === 'google'
                    && isset($context['quoteTypeId']) && $context['quoteTypeId'] === $quoteTypeId;
            }))
            ->once();
        
        $this->assertTrue($result);
    }

    public function test_trigger_facebook_conversion_logs_success(): void
    {
        Log::spy();
        
        $mockCapi = Mockery::mock('alias:'.Capi::class);
        $mockCapi->shouldReceive('request')
            ->andReturn((object) ['success' => true]);

        $result = $this->service->triggerFacebookConversion('test-uuid-success', QuoteTypeId::Car);

        Log::shouldHaveReceived('info')
            ->with(\Mockery::pattern('/ConversionApiService - facebook conversion API call successful/'), \Mockery::type('array'))
            ->once();
        
        $this->assertTrue($result);
    }

    public function test_trigger_facebook_conversion_logs_error_on_failure(): void
    {
        Log::spy();
        
        $mockCapi = Mockery::mock('alias:'.Capi::class);
        $mockCapi->shouldReceive('request')
            ->andThrow(new \Exception('API Error'));

        $result = $this->service->triggerFacebookConversion('test-uuid-error', QuoteTypeId::Car);

        Log::shouldHaveReceived('error')
            ->with(\Mockery::pattern('/ConversionApiService - facebook conversion API call exception/'), \Mockery::on(function ($context) {
                return isset($context['exception']) && is_array($context['exception'])
                    && isset($context['exception']['message'])
                    && isset($context['exception']['trace'])
                    && isset($context['exception']['code']);
            }))
            ->once();
        
        $this->assertFalse($result);
    }

    public function test_works_with_different_quote_type_ids(): void
    {
        $mockCapi = Mockery::mock('alias:'.Capi::class);
        $mockCapi->shouldReceive('request')
            ->andReturn((object) ['success' => true]);

        $quoteTypeIds = [
            QuoteTypeId::Car,
            QuoteTypeId::Travel,
            QuoteTypeId::Health,
            QuoteTypeId::Business,
        ];

        foreach ($quoteTypeIds as $quoteTypeId) {
            $result = $this->service->triggerFacebookConversion('test-uuid-'.$quoteTypeId, $quoteTypeId);
            $this->assertTrue($result);
        }
    }
}
