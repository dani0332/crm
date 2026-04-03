<?php

declare(strict_types=1);

use App\Exports\CarQuoteExport;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

test('car quote export headings include engagement level after repair type', function () {
    $headings = app(CarQuoteExport::class)->headings();

    expect(array_slice($headings, -3))->toBe([
        'IMCRM SUB-SOURCE',
        'REPAIR TYPE',
        'ENGAGEMENT LEVEL',
    ]);
});
