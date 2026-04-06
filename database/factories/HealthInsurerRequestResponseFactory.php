<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HealthInsurerRequestResponse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class HealthInsurerRequestResponseFactory extends Factory
{
    protected $model = HealthInsurerRequestResponse::class;

    public function definition(): array
    {
        $quoteNumber = 'INS-QUOTE-'.strtoupper(Str::random(6));

        return [
            'quote_uuid' => Str::uuid()->toString(),
            'request' => json_encode([
                'InsuredInfo' => [
                    [
                        'MemberSeqNo' => 1,
                        'Name' => fake()->name(),
                        'DateOfBirth' => fake()->date('d-m-Y', '-30 years'),
                        'Gender' => fake()->randomElement(['M', 'F']),
                    ],
                ],
                'QuoteInfo' => [
                    'PartnerPremium' => 1000,
                    'ProductType' => 'Health',
                    'PolicyType' => 'Individual',
                    'PlanType' => 'Basic',
                    'DeductibleAmount' => 0,
                    'CoInsurancePercent' => 0,
                    'DentalCover' => 'N',
                    'OpticalCover' => 'N',
                    'SalaryBand' => '1',
                    'NoOfPersonsToInsure' => 1,
                ],
                'SponsorInfo' => [
                    'PreviousVisaEmirate' => 1,
                ],
            ]),
            'response' => json_encode([
                'QuoteInfo' => [
                    'QuotationNo' => $quoteNumber,
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
