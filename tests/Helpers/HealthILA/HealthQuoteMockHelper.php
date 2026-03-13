<?php

namespace Tests\Helpers\HealthILA;

use App\Enums\QuoteTypeId;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;
use Mockery;
use Mockery\MockInterface;

class HealthQuoteMockHelper
{
    /**
     * Mock CapiRequestService to simulate external Health API behavior.
     */
    public static function mockCapiRequestService(string $testUuid): MockInterface
    {
        $mock = Mockery::mock('alias:App\Facades\Capi');
        $mock->shouldReceive('request')
            ->once()
            ->with('/api/health/create', 'post', Mockery::type('array'))
            ->andReturnUsing(function ($endpoint, $method, $data) use ($testUuid) {
                return self::simulateCapiResponse($testUuid, $data);
            });

        return $mock;
    }

    /**
     * Mock KEN Service for retrieving plans.
     */
    public static function mockKenService(array $plans = []): MockInterface
    {
        $mock = Mockery::mock('alias:App\Facades\Ken');

        $defaultPlans = ! empty($plans) ? $plans : self::getDefaultPlans();

        $mock->shouldReceive('request')
            ->with('/health/get-quote-plans', 'post', Mockery::type('array'))
            ->andReturn((object) [
                'quotes' => [
                    'plans' => $defaultPlans,
                ],
            ]);

        return $mock;
    }

    /**
     * Mock BIRD Service for email workflows.
     */
    public static function mockBirdService(): MockInterface
    {
        $mock = Mockery::mock('alias:App\Services\BirdService');
        $mock->shouldReceive('triggerWebHookRequest')
            ->andReturn((object) [
                'status_code' => 200,
                'message' => 'Workflow triggered successfully',
            ]);

        $mock->shouldReceive('isFollowupExecuted')
            ->andReturn(false);

        $mock->shouldReceive('createQuoteWorkFlowDetails')
            ->andReturn(true);

        return $mock;
    }

    /**
     * Simulate what the CAPI service does: creates records and returns response.
     */
    private static function simulateCapiResponse(string $testUuid, array $data): object
    {
        $personalQuote = PersonalQuote::create([
            'uuid' => $testUuid,
            'quote_type_id' => QuoteTypeId::Health,
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'email' => $data['email'],
            'mobile_no' => $data['mobileNo'],
            'dob' => $data['dob'],
            'source' => $data['source'] ?? 'IMCRM',
            'device' => $data['device'] ?? 'DESKTOP',
            'code' => 'HEA-'.uniqid(),
            'created_by_id' => auth()->user()?->id ?? null,
            'advisor_id' => $data['advisorId'] ?? null,
        ]);

        HealthQuote::create([
            'personal_quote_id' => $personalQuote->id,
            'nationality_id' => $data['nationalityId'],
            'price_starting_from' => $data['priceStartingFrom'] ?? null,
        ]);

        return (object) [
            'quoteUID' => $testUuid,
            'msg' => null,
            'errors' => null,
        ];
    }

    /**
     * Get default plans for Health quotes.
     */
    private static function getDefaultPlans(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Basic Health Plan',
                'premium' => 2500.00,
                'coverage' => 'Basic Coverage',
            ],
            [
                'id' => 2,
                'name' => 'Premium Health Plan',
                'premium' => 5000.00,
                'coverage' => 'Premium Coverage',
            ],
        ];
    }
}
