<?php

namespace Tests\Helpers\CyberILA;

use App\Enums\QuoteTypeId;
use App\Models\CyberQuote;
use App\Models\PersonalQuote;
use Mockery;
use Mockery\MockInterface;

class CyberQuoteMockHelper
{
    /**
     * Mock CapiRequestService to simulate external Cyber API behavior.
     */
    public static function mockCapiRequestService(string $testUuid): MockInterface
    {
        $mock = Mockery::mock('alias:App\Facades\Capi');
        $mock->shouldReceive('request')
            ->once()
            ->with('/api/cyber/create', 'post', Mockery::type('array'))
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
            ->with('/cyber/get-quote-plans', 'post', Mockery::type('array'))
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
            'quote_type_id' => QuoteTypeId::Cyber,
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'email' => $data['email'],
            'mobile_no' => $data['mobileNo'],
            'dob' => $data['dob'],
            'source' => $data['source'] ?? 'IMCRM',
            'device' => $data['device'] ?? 'DESKTOP',
            'code' => 'CYBER-'.uniqid(),
            'created_by_id' => auth()->user()?->id ?? null,
            'advisor_id' => $data['advisorId'] ?? null,
        ]);

        CyberQuote::create([
            'personal_quote_id' => $personalQuote->id,
            'emirate_of_registration_id' => $data['emirateOfRegistrationId'],
            'nationality_id' => $data['nationalityId'],
            'coverage_id' => $data['coverageId'] ?? null,
        ]);

        return (object) [
            'quoteUID' => $testUuid,
            'msg' => null,
            'errors' => null,
        ];
    }

    /**
     * Get default plans for Cyber quotes.
     */
    private static function getDefaultPlans(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Basic Cyber Coverage',
                'premium' => 250.00,
                'coverage' => 'Up to 500K',
            ],
            [
                'id' => 2,
                'name' => 'Premium Cyber Coverage',
                'premium' => 500.00,
                'coverage' => 'Up to 1M',
            ],
        ];
    }
}
