<?php

declare(strict_types=1);

use App\Exports\Reports\ConversionOptimizationReportExport;

test('conversion optimization export headings omit batch columns', function (): void {
    $export = (new ReflectionClass(ConversionOptimizationReportExport::class))->newInstanceWithoutConstructor();

    expect($export->headings())->toBe([
        'Advisor Name',
        'Total Leads',
        'Sale Leads',
        'Conversion',
        'Ranking',
        'Expected Sales',
        'Required Sales',
        'New Conversion %',
        'Current Cap',
        'Suggested Cap',
    ]);
});

test('conversion optimization export maps advisor-only rows', function (): void {
    $export = (new ReflectionClass(ConversionOptimizationReportExport::class))->newInstanceWithoutConstructor();

    $record = (object) [
        'advisor_name' => 'Advisor 1',
        'total_leads' => 12,
        'sale_leads' => 3,
        'conversion' => 25,
        'ranking' => 4,
        'expected_sales' => 5.5,
        'required_sales' => 2.5,
        'new_conversion' => 45.83,
        'current_cap' => 20,
        'suggested_cap' => 7,
    ];

    expect($export->map($record))->toBe([
        'Advisor 1',
        12.0,
        3.0,
        25.0,
        4.0,
        5.5,
        2.5,
        45.83,
        20.0,
        7.0,
    ]);
});
