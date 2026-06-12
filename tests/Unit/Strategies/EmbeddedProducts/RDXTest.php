<?php

use App\Enums\QuoteTypeId;
use App\Strategies\EmbeddedProducts\RDX;

beforeEach(function () {
    $this->rdx = new RDX;
});

describe('processReportRecord', function () {
    test('processes bike quote record correctly', function () {
        // Create mock bike make and model using stdClass
        $mockBikeMake = (object) ['text' => 'Yamaha'];
        $mockBikeModel = (object) ['text' => 'R1'];

        // Create mock bike quote using stdClass
        $mockBikeQuote = (object) [
            'bikeMake' => $mockBikeMake,
            'bikeModel' => $mockBikeModel,
        ];

        // Create mock quote object
        $mockQuoteObject = (object) [
            'bikeQuote' => $mockBikeQuote,
            'advisor' => (object) ['name' => 'John Doe'],
        ];

        // Create mock item
        $mockItem = (object) [
            'quote_type_id' => QuoteTypeId::Bike,
        ];

        // Use reflection to access the protected method
        $reflection = new ReflectionClass($this->rdx);
        $method = $reflection->getMethod('processReportRecord');
        $method->setAccessible(true);

        $result = $method->invoke($this->rdx, $mockQuoteObject, $mockItem);

        expect($result->lob)->toBe('Bike')
            ->and($result->vehicle)->toBe('Yamaha R1')
            ->and($result->advisor_name)->toBe('John Doe');
    });

    test('processes car quote record correctly', function () {
        // Create mock car make and model
        $mockCarMake = (object) ['text' => 'Toyota'];
        $mockCarModel = (object) ['text' => 'Camry'];

        // Create mock quote object for car
        $mockQuoteObject = (object) [
            'carMake' => $mockCarMake,
            'carModel' => $mockCarModel,
            'advisor' => (object) ['name' => 'Jane Smith'],
        ];

        // Create mock item
        $mockItem = (object) [
            'quote_type_id' => QuoteTypeId::Car,
        ];

        // Use reflection to access the protected method
        $reflection = new ReflectionClass($this->rdx);
        $method = $reflection->getMethod('processReportRecord');
        $method->setAccessible(true);

        $result = $method->invoke($this->rdx, $mockQuoteObject, $mockItem);

        expect($result->lob)->toBe('Car')
            ->and($result->vehicle)->toBe('Toyota Camry')
            ->and($result->advisor_name)->toBe('Jane Smith');
    });

    test('handles missing bike make and model gracefully', function () {
        // Create mock bike quote without make/model
        $mockBikeQuote = (object) [
            'bikeMake' => null,
            'bikeModel' => null,
        ];

        // Create mock quote object
        $mockQuoteObject = (object) [
            'bikeQuote' => $mockBikeQuote,
            'advisor' => null,
        ];

        // Create mock item
        $mockItem = (object) [
            'quote_type_id' => QuoteTypeId::Bike,
        ];

        // Use reflection to access the protected method
        $reflection = new ReflectionClass($this->rdx);
        $method = $reflection->getMethod('processReportRecord');
        $method->setAccessible(true);

        $result = $method->invoke($this->rdx, $mockQuoteObject, $mockItem);

        expect($result->lob)->toBe('Bike')
            ->and($result->vehicle)->toBe(' ')
            ->and($result->advisor_name)->toBe('');
    });

    test('handles missing car make and model gracefully', function () {
        // Create mock quote object without car make/model
        $mockQuoteObject = (object) [
            'carMake' => null,
            'carModel' => null,
            'advisor' => null,
        ];

        // Create mock item
        $mockItem = (object) [
            'quote_type_id' => QuoteTypeId::Car,
        ];

        // Use reflection to access the protected method
        $reflection = new ReflectionClass($this->rdx);
        $method = $reflection->getMethod('processReportRecord');
        $method->setAccessible(true);

        $result = $method->invoke($this->rdx, $mockQuoteObject, $mockItem);

        expect($result->lob)->toBe('Car')
            ->and($result->vehicle)->toBe(' ')
            ->and($result->advisor_name)->toBe('');
    });

    test('handles unsupported quote types with N/A', function () {
        // Create mock quote object
        $mockQuoteObject = (object) [
            'advisor' => (object) ['name' => 'Test Advisor'],
        ];

        // Create mock item with health quote type
        $mockItem = (object) [
            'quote_type_id' => QuoteTypeId::Health,
        ];

        // Use reflection to access the protected method
        $reflection = new ReflectionClass($this->rdx);
        $method = $reflection->getMethod('processReportRecord');
        $method->setAccessible(true);

        $result = $method->invoke($this->rdx, $mockQuoteObject, $mockItem);

        expect($result->lob)->toBe('Health')
            ->and($result->vehicle)->toBe('N/A')
            ->and($result->advisor_name)->toBe('Test Advisor');
    });

    test('processes bike with make only', function () {
        // Create mock bike make only
        $mockBikeMake = (object) ['text' => 'Kawasaki'];

        // Create mock bike quote
        $mockBikeQuote = (object) [
            'bikeMake' => $mockBikeMake,
            'bikeModel' => null,
        ];

        // Create mock quote object
        $mockQuoteObject = (object) [
            'bikeQuote' => $mockBikeQuote,
            'advisor' => (object) ['name' => 'Test'],
        ];

        // Create mock item
        $mockItem = (object) [
            'quote_type_id' => QuoteTypeId::Bike,
        ];

        // Use reflection to access the protected method
        $reflection = new ReflectionClass($this->rdx);
        $method = $reflection->getMethod('processReportRecord');
        $method->setAccessible(true);

        $result = $method->invoke($this->rdx, $mockQuoteObject, $mockItem);

        expect($result->lob)->toBe('Bike')
            ->and($result->vehicle)->toBe('Kawasaki ')
            ->and($result->advisor_name)->toBe('Test');
    });

    test('processes car with model only', function () {
        // Create mock car model only
        $mockCarModel = (object) ['text' => 'Accord'];

        // Create mock quote object
        $mockQuoteObject = (object) [
            'carMake' => null,
            'carModel' => $mockCarModel,
            'advisor' => (object) ['name' => 'Advisor Test'],
        ];

        // Create mock item
        $mockItem = (object) [
            'quote_type_id' => QuoteTypeId::Car,
        ];

        // Use reflection to access the protected method
        $reflection = new ReflectionClass($this->rdx);
        $method = $reflection->getMethod('processReportRecord');
        $method->setAccessible(true);

        $result = $method->invoke($this->rdx, $mockQuoteObject, $mockItem);

        expect($result->lob)->toBe('Car')
            ->and($result->vehicle)->toBe(' Accord')
            ->and($result->advisor_name)->toBe('Advisor Test');
    });
});

afterEach(function () {
    Mockery::close();
});
