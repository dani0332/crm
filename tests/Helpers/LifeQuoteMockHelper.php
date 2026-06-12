<?php

namespace Tests\Helpers;

use App\Enums\QuoteTypeId;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Services\CapiRequestService;
use Mockery;
use Mockery\MockInterface;

class LifeQuoteMockHelper
{
    /**
     * Mock CapiRequestService to simulate external API behavior.
     */
    public static function mockCapiRequestService(string $testUuid): MockInterface
    {
        $mock = Mockery::mock('alias:'.CapiRequestService::class);
        $mock->shouldReceive('sendCAPIRequest')
            ->once()
            ->with('/api/v2-save-life-quote', Mockery::type('array'))
            ->andReturnUsing(function ($endpoint, $data) use ($testUuid) {
                return self::simulateCapiResponse($testUuid, $data);
            });

        return $mock;
    }

    /**
     * Simulate what the CAPI service does: creates records and returns response.
     */
    private static function simulateCapiResponse(string $testUuid, array $data): object
    {
        $personalQuote = PersonalQuote::create([
            'uuid' => $testUuid,
            'quote_type_id' => QuoteTypeId::Life,
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'email' => $data['email'],
            'mobile_no' => $data['mobileNo'],
            'dob' => $data['dob'],
            'source' => $data['source'] ?? 'TEST',
            'device' => $data['device'] ?? 'DESKTOP',
            'code' => 'TEST-'.uniqid(),
            'created_by_id' => auth()->user()->id,
            'advisor_id' => $data['advisorId'] ?? null,
        ]);

        LifeQuote::create([
            'personal_quote_id' => $personalQuote->id,
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
            'email' => $data['email'],
            'mobile_no' => $data['mobileNo'],
            'sum_insured_value' => $data['sumInsuredValue'],
            'nationality_id' => $data['nationalityId'],
            'sum_insured_currency_id' => $data['sumInsuredCurrencyId'],
            'marital_status_id' => $data['maritalStatusId'],
            'purpose_of_insurance_id' => $data['purposeOfInsuranceId'],
            'number_of_years_id' => $data['numberOfYearsId'],
            'is_smoker' => $data['isSmoker'],
            'gender' => $data['gender'],
            'others_info' => $data['othersInfo'] ?? null,
            'height' => $data['height'] ?? null,
            'weight' => $data['weight'] ?? null,
            'bmi' => $data['bmi'] ?? null,
            'age' => $data['age'] ?? null,
        ]);

        return (object) [
            'quoteUID' => $testUuid,
            'msg' => null,
            'errors' => null,
        ];
    }
}
