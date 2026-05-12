<?php

use App\Models\PersonalQuote;
use App\Repositories\LifeQuoteRepository;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Support\Facades\Config;
use Tests\Helpers\TestSchemaCreator;

it('normalizes nested array date value so Carbon::parse does not receive an array', function (): void {
    $subject = new class
    {
        use GenericQueriesAllLobs;
    };
    $method = new ReflectionMethod($subject, 'normalizeDateValue');
    $method->setAccessible(true);

    $result = $method->invoke($subject, [['2024-01-01']]);

    expect($result)->toBe('2024-01-01');
});

it('normalizes deeply nested array to scalar date string', function (): void {
    $subject = new class
    {
        use GenericQueriesAllLobs;
    };
    $method = new ReflectionMethod($subject, 'normalizeDateValue');
    $method->setAccessible(true);

    $result = $method->invoke($subject, [[['2024-01-15']]]);

    expect($result)->toBe('2024-01-15');
});

it('returns scalar for flat string so Carbon::parse is safe', function (): void {
    $subject = new class
    {
        use GenericQueriesAllLobs;
    };
    $method = new ReflectionMethod($subject, 'normalizeDateValue');
    $method->setAccessible(true);

    $result = $method->invoke($subject, '2024-01-20');

    expect($result)->toBe('2024-01-20');
});

describe('adjustQueryByDateFilters with request params', function () {
    beforeEach(function (): void {
        TestSchemaCreator::createMinimalSchema();
        Config::set('constants.DB_DATE_FORMAT_MATCH', 'Y-m-d');
    });

    it('does not throw when booking_date is sent as nested array from frontend', function (): void {
        $query = PersonalQuote::query();
        $requestParams = [
            'booking_date' => [
                ['2024-01-01'],
                ['2024-01-31'],
            ],
        ];

        $repository = new LifeQuoteRepository;
        $repository->adjustQueryByDateFilters($query, 'personal_quotes', $requestParams, true);

        $bindings = $query->getBindings();
        expect($bindings)->toContain('2024-01-01', '2024-01-31');
    });

    it('does not throw when payment_due_date is sent as nested array from frontend', function (): void {
        $query = PersonalQuote::query();
        $requestParams = [
            'payment_due_date' => [
                ['2024-02-01'],
                ['2024-02-15'],
            ],
        ];

        $repository = new LifeQuoteRepository;
        $repository->adjustQueryByDateFilters($query, 'personal_quotes', $requestParams, true);

        $bindings = $query->getBindings();
        expect($bindings)->toContain('2024-02-01', '2024-02-15');
    });

    it('accepts flat array date range and applies filter', function (): void {
        $query = PersonalQuote::query();
        $requestParams = [
            'booking_date' => ['2024-03-01', '2024-03-31'],
        ];

        $repository = new LifeQuoteRepository;
        $repository->adjustQueryByDateFilters($query, 'personal_quotes', $requestParams, true);

        $bindings = $query->getBindings();
        expect($bindings)->toContain('2024-03-01', '2024-03-31');
    });
});
