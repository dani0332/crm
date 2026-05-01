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

        $this->service = new ConversionApiService;
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
            ->with('ConversionApiService - Calling facebook conversion API', Mockery::on(function ($context) use ($quoteTypeId) {
                return isset($context['eventType']) && $context['eventType'] === 'Purchase'
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
            ->with('ConversionApiService - Calling google conversion API', Mockery::on(function ($context) use ($quoteTypeId) {
                return isset($context['eventType']) && $context['eventType'] === 'Purchase'
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
            ->with('ConversionApiService - facebook conversion API call successful', Mockery::type('array'))
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
            ->with('ConversionApiService - facebook conversion API call exception', Mockery::on(function ($context) {
                return isset($context['exception']) && is_array($context['exception'])
                    && isset($context['exception']['message'])
                    && isset($context['exception']['trace'])
                    && isset($context['exception']['code'])
                    && isset($context['eventType'])
                    && isset($context['platform'])
                    && isset($context['error']);
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

    public function test_facebook_conversion_sends_only_required_params(): void
    {
        $quoteUID = 'NREFC7RS';
        $quoteTypeId = 19;

        $mockCapi = Mockery::mock('alias:'.Capi::class);
        $mockCapi->shouldReceive('request')
            ->once()
            ->with('/api/v1-trigger-facebook-event-conversion', 'post', Mockery::on(function ($payload) use ($quoteUID, $quoteTypeId) {
                // Verify payload has exactly 3 keys: quoteUID, quoteTypeId, eventType
                if (count($payload) !== 3) {
                    return false;
                }

                // Verify the keys exist
                if (! isset($payload['quoteUID']) || ! isset($payload['quoteTypeId']) || ! isset($payload['eventType'])) {
                    return false;
                }

                // Verify the values
                return $payload['quoteUID'] === $quoteUID
                    && $payload['quoteTypeId'] === $quoteTypeId
                    && $payload['eventType'] === 'Purchase';
            }))
            ->andReturn((object) ['success' => true]);

        $result = $this->service->triggerFacebookConversion($quoteUID, $quoteTypeId);

        $this->assertTrue($result);
    }

    public function test_google_conversion_sends_only_required_params(): void
    {
        $quoteUID = '6UTE2JXU';
        $quoteTypeId = 1;

        $mockCapi = Mockery::mock('alias:'.Capi::class);
        $mockCapi->shouldReceive('request')
            ->once()
            ->with('/api/v1-trigger-google-event-conversion', 'post', Mockery::on(function ($payload) use ($quoteUID, $quoteTypeId) {
                // Verify payload has exactly 3 keys: quoteUID, quoteTypeId, eventType
                if (count($payload) !== 3) {
                    return false;
                }

                // Verify the keys exist
                if (! isset($payload['quoteUID']) || ! isset($payload['quoteTypeId']) || ! isset($payload['eventType'])) {
                    return false;
                }

                // Verify the values
                return $payload['quoteUID'] === $quoteUID
                    && $payload['quoteTypeId'] === $quoteTypeId
                    && $payload['eventType'] === 'Purchase';
            }))
            ->andReturn((object) ['success' => true]);

        $result = $this->service->triggerGoogleConversion($quoteUID, $quoteTypeId);

        $this->assertTrue($result);
    }

    public function test_quote_type_id_remains_dynamic(): void
    {
        $mockCapi = Mockery::mock('alias:'.Capi::class);

        // Test various quote type IDs
        $testCases = [
            ['quoteUID' => 'NREFC7RS', 'quoteTypeId' => 19],
            ['quoteUID' => '6UTE2JXU', 'quoteTypeId' => 1],
            ['quoteUID' => 'TEST123', 'quoteTypeId' => 5],
            ['quoteUID' => 'ABCD1234', 'quoteTypeId' => 10],
            ['quoteUID' => 'XYZ789', 'quoteTypeId' => 25],
        ];

        foreach ($testCases as $testCase) {
            $mockCapi->shouldReceive('request')
                ->once()
                ->with(Mockery::any(), 'post', Mockery::on(function ($payload) use ($testCase) {
                    return $payload['quoteUID'] === $testCase['quoteUID']
                        && $payload['quoteTypeId'] === $testCase['quoteTypeId']
                        && $payload['eventType'] === 'Purchase';
                }))
                ->andReturn((object) ['success' => true]);

            $result = $this->service->triggerFacebookConversion($testCase['quoteUID'], $testCase['quoteTypeId']);
            $this->assertTrue($result);
        }
    }

    public function test_both_apis_accept_same_payload_structure(): void
    {
        $quoteUID = 'TEST-UUID-123';
        $quoteTypeId = 15;

        $mockCapi = Mockery::mock('alias:'.Capi::class);

        // Both Facebook and Google should receive identical payload structure
        $expectedPayload = [
            'quoteUID' => $quoteUID,
            'quoteTypeId' => $quoteTypeId,
            'eventType' => 'Purchase',
        ];

        $mockCapi->shouldReceive('request')
            ->once()
            ->with('/api/v1-trigger-facebook-event-conversion', 'post', $expectedPayload)
            ->andReturn((object) ['success' => true]);

        $mockCapi->shouldReceive('request')
            ->once()
            ->with('/api/v1-trigger-google-event-conversion', 'post', $expectedPayload)
            ->andReturn((object) ['success' => true]);

        $facebookResult = $this->service->triggerFacebookConversion($quoteUID, $quoteTypeId);
        $googleResult = $this->service->triggerGoogleConversion($quoteUID, $quoteTypeId);

        $this->assertTrue($facebookResult);
        $this->assertTrue($googleResult);
    }

    public function test_trigger_facebook_conversion_returns_false_when_response_has_errors(): void
    {
        Log::spy();

        $mockCapi = Mockery::mock('alias:'.Capi::class);
        $mockCapi->shouldReceive('request')
            ->once()
            ->andReturn((object) [
                'errors' => ['Error message from API'],
                'status' => 'failed',
            ]);

        $result = $this->service->triggerFacebookConversion('test-uuid-error', QuoteTypeId::Car);

        $this->assertFalse($result);

        Log::shouldHaveReceived('error')
            ->with('ConversionApiService - facebook conversion API returned errors', Mockery::on(function ($context) {
                return isset($context['errors'])
                    && isset($context['response'])
                    && isset($context['platform'])
                    && $context['platform'] === 'facebook';
            }))
            ->once();
    }

    public function test_trigger_google_conversion_returns_false_when_response_has_errors(): void
    {
        Log::spy();

        $mockCapi = Mockery::mock('alias:'.Capi::class);
        $mockCapi->shouldReceive('request')
            ->once()
            ->andReturn((object) [
                'errors' => ['Invalid quoteUID'],
                'status' => 'failed',
            ]);

        $result = $this->service->triggerGoogleConversion('test-uuid-error', QuoteTypeId::Travel);

        $this->assertFalse($result);

        Log::shouldHaveReceived('error')
            ->with('ConversionApiService - google conversion API returned errors', Mockery::on(function ($context) {
                return isset($context['errors'])
                    && isset($context['platform'])
                    && $context['platform'] === 'google';
            }))
            ->once();
    }

    public function test_trigger_facebook_conversion_returns_false_when_response_is_null(): void
    {
        Log::spy();

        $mockCapi = Mockery::mock('alias:'.Capi::class);
        $mockCapi->shouldReceive('request')
            ->once()
            ->andReturn(null);

        $result = $this->service->triggerFacebookConversion('test-uuid-null', QuoteTypeId::Car);

        $this->assertFalse($result);

        Log::shouldHaveReceived('error')
            ->with('ConversionApiService - facebook conversion API returned empty response', Mockery::on(function ($context) {
                return isset($context['platform'])
                    && $context['platform'] === 'facebook';
            }))
            ->once();
    }

    public function test_trigger_google_conversion_returns_false_when_response_is_empty(): void
    {
        Log::spy();

        $mockCapi = Mockery::mock('alias:'.Capi::class);
        $mockCapi->shouldReceive('request')
            ->once()
            ->andReturn(null);

        $result = $this->service->triggerGoogleConversion('test-uuid-null', QuoteTypeId::Health);

        $this->assertFalse($result);

        Log::shouldHaveReceived('error')
            ->with('ConversionApiService - google conversion API returned empty response', Mockery::on(function ($context) {
                return isset($context['platform'])
                    && $context['platform'] === 'google';
            }))
            ->once();
    }

    public function test_trigger_facebook_conversion_validates_response_before_logging_success(): void
    {
        Log::spy();

        $mockCapi = Mockery::mock('alias:'.Capi::class);

        // First call with errors - should not log success
        $mockCapi->shouldReceive('request')
            ->once()
            ->andReturn((object) ['errors' => ['API Error']]);

        $result1 = $this->service->triggerFacebookConversion('test-uuid-1', QuoteTypeId::Car);
        $this->assertFalse($result1);

        // Second call with valid response - should log success
        $mockCapi->shouldReceive('request')
            ->once()
            ->andReturn((object) ['success' => true, 'message' => 'Event sent']);

        $result2 = $this->service->triggerFacebookConversion('test-uuid-2', QuoteTypeId::Car);
        $this->assertTrue($result2);

        // Verify error was logged for first call
        Log::shouldHaveReceived('error')
            ->with('ConversionApiService - facebook conversion API returned errors', Mockery::type('array'))
            ->once();

        // Verify success was logged only for second call
        Log::shouldHaveReceived('info')
            ->with('ConversionApiService - facebook conversion API call successful', Mockery::type('array'))
            ->once();
    }
}
