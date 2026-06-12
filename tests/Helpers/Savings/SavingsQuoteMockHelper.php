<?php

namespace Tests\Helpers\Savings;

use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use App\Models\SavingsQuote;
use App\Services\HttpRequestService;
use App\Services\Quotes\SavingsQuoteService;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;

class SavingsQuoteMockHelper
{
    public static function mockHttpRequestService(int $statusCode = 200): MockInterface
    {
        $mockService = Mockery::mock(HttpRequestService::class);
        $mockService->shouldReceive('processRequest')->andReturn($statusCode);

        return $mockService;
    }

    public static function createTestSavingsQuote(array $overrides = []): PersonalQuote
    {
        return DB::transaction(function () use ($overrides) {
            $defaults = [
                'uuid' => 'test-savings-quote-uuid-'.uniqid(),
                'quote_type_id' => QuoteTypeId::Savings,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
                'mobile_no' => '+971501234567',
                'code' => 'SAV-'.uniqid(),
                'created_by_id' => auth()->check() ? auth()->id() : 1,
            ];

            $personalQuote = PersonalQuote::create(array_merge($defaults, $overrides));

            SavingsQuote::create([
                'personal_quote_id' => $personalQuote->id,
                'marital_status_id' => $overrides['marital_status_id'] ?? 1,
                'tenure_id' => $overrides['tenure_id'] ?? 1,
                'purpose_id' => $overrides['purpose_id'] ?? 1,
                'currency_id' => $overrides['currency_id'] ?? 1,
                'investment_amount' => $overrides['investment_amount'] ?? 10000,
                'investment_criteria_id' => $overrides['investment_criteria_id'] ?? 1,
            ]);

            return $personalQuote->load('savingsQuote');
        });
    }

    public static function createMockPlanData(int $planId = 1, array $overrides = []): object
    {
        $defaults = [
            'id' => $planId,
            'name' => 'Test Savings Plan '.$planId,
            'providerCode' => 'TEST',
            'providerName' => 'Test Provider',
            'planTypeId' => 1,
            'actualPremium' => 1000,
            'insurerQuoteNo' => 'INS-'.uniqid(),
            'isDisabled' => false,
            'isManualUpdate' => false,
            'eligibilities' => [
                (object) ['code' => 'minimumInvestmentAmount', 'value' => '5000'],
                (object) ['code' => 'policyTerm', 'value' => '10'],
            ],
            'includedBenefits' => [],
            'keyFeatureDocument' => [],
            'description' => 'Test plan description',
            'policyWordings' => [],
            'fundDetails' => [],
        ];

        return (object) array_merge($defaults, $overrides);
    }

    public static function mockSavingsQuoteService($httpService, $plansResponse): MockInterface
    {
        $mockService = Mockery::mock(SavingsQuoteService::class, [$httpService])->makePartial();
        $mockService->shouldReceive('getQuotePlans')->andReturn($plansResponse);

        return $mockService;
    }

    public static function createPlansResponse(array $regular = [], array $lumpsum = []): object
    {
        return (object) [
            'quotes' => (object) [
                'plans' => (object) [
                    'regular' => $regular,
                    'lumpsum' => $lumpsum,
                ],
            ],
        ];
    }
}
