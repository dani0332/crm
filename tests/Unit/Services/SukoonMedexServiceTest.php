<?php

use App\Enums\QuoteTypeId;
use App\Enums\VehicleTypeEnum;
use App\Services\SukoonMedexService;
use Illuminate\Support\Facades\Config;

use function Pest\Laravel\partialMock;

beforeEach(function () {
    Config::set('constants.SUKOON_API_URL', 'https://test-api.sukoon.com');
    Config::set('constants.SUKOON_API_VERSION', '1');
    $this->service = partialMock(SukoonMedexService::class);
});

function setServiceProperties($service, array $properties): ReflectionMethod
{
    $reflection = new ReflectionClass(SukoonMedexService::class);

    foreach ($properties as $name => $value) {
        $property = $reflection->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($service, $value);
    }

    $method = $reflection->getMethod('prepareAdditionalData');
    $method->setAccessible(true);

    return $method;
}

describe('prepareAdditionalData', function () {
    test('returns personal_sports_mc plan for bike quote type', function () {
        $method = setServiceProperties($this->service, [
            'quoteTypeId' => QuoteTypeId::Bike,
            'currentQuote' => (object) ['vehicle_type_id' => null],
            'productSlug' => 'sukoon',
            'paymentPlan' => 'monthly',
            'amountDisclaimerText' => 'Test disclaimer',
            'quotePolicy' => 'POL123',
        ]);

        $result = $method->invoke($this->service);

        expect($result['plan_option'])->toBe('sukoon-personal_sports_mc')
            ->and($result['form_name'])->toBe('plan_picker')
            ->and($result['payment_plan'])->toBe('monthly')
            ->and($result['policy_number'])->toBe('POL123');
    });

    test('returns personal_non_commercial_vehicles plan for regular car', function () {
        $method = setServiceProperties($this->service, [
            'quoteTypeId' => QuoteTypeId::Car,
            'currentQuote' => (object) ['vehicle_type_id' => 1],
            'productSlug' => 'medex',
            'paymentPlan' => 'annual',
            'amountDisclaimerText' => '',
            'quotePolicy' => 'POL456',
        ]);

        $result = $method->invoke($this->service);

        expect($result['plan_option'])->toBe('medex-personal_non_commercial_vehicles');
    });

    test('returns personal_sports_mc plan for car with motorcycle vehicle type', function () {
        $method = setServiceProperties($this->service, [
            'quoteTypeId' => QuoteTypeId::Car,
            'currentQuote' => (object) ['vehicle_type_id' => VehicleTypeEnum::MOTOR_CYCLE->value],
            'productSlug' => 'sukoon',
            'paymentPlan' => 'monthly',
            'amountDisclaimerText' => 'Bike renewal',
            'quotePolicy' => 'POL789',
        ]);

        $result = $method->invoke($this->service);

        expect($result['plan_option'])->toBe('sukoon-personal_sports_mc');
    });

    test('returns null plan_option for unsupported quote types', function () {
        $method = setServiceProperties($this->service, [
            'quoteTypeId' => QuoteTypeId::Health,
            'currentQuote' => (object) ['vehicle_type_id' => null],
            'productSlug' => 'sukoon',
            'paymentPlan' => 'monthly',
            'amountDisclaimerText' => '',
            'quotePolicy' => 'POL999',
        ]);

        $result = $method->invoke($this->service);

        expect($result['plan_option'])->toBeNull();
    });
});
