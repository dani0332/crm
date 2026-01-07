<?php

declare(strict_types=1);

use App\Exports\NonPUAQuoteExport;

test('NonPUAQuoteExport headings include the correct department column name', function (): void {
    $export = (new ReflectionClass(NonPUAQuoteExport::class))->newInstanceWithoutConstructor();

    $headings = $export->headings();

    expect($headings)->toBeArray()
        ->toContain('Department Name');

    expect(array_search('Department Name', $headings, true))->toBe(9);
});

test('NonPUAQuoteExport map outputs department value when present', function (): void {
    $export = (new ReflectionClass(NonPUAQuoteExport::class))->newInstanceWithoutConstructor();

    $quote = (object) [
        'RefID' => 'REF-1',
        'premiumauthorized' => 'Yes',
        'paymentauthdate' => null,
        'leadstatus' => 'Lead Status',
        'paymentstatus' => 'Payment Status',
        'source' => 'Source',
        'make' => 'Make',
        'model' => 'Model',
        'assignedadvisoremail' => 'advisor@example.com',
        'departmentname' => 'Sales',
    ];

    $mapped = $export->map($quote);

    expect($mapped)->toBeArray()
        ->and($mapped[9])->toBe('Sales');
});

test('NonPUAQuoteExport map outputs empty string when department is missing', function (): void {
    $export = (new ReflectionClass(NonPUAQuoteExport::class))->newInstanceWithoutConstructor();

    $quote = (object) [
        'RefID' => 'REF-1',
        'premiumauthorized' => 'Yes',
        'paymentauthdate' => null,
        'leadstatus' => 'Lead Status',
        'paymentstatus' => 'Payment Status',
        'source' => 'Source',
        'make' => 'Make',
        'model' => 'Model',
        'assignedadvisoremail' => 'advisor@example.com',
        // departmentname intentionally omitted
    ];

    $mapped = $export->map($quote);

    expect($mapped)->toBeArray()
        ->and($mapped[9])->toBe('');
});


