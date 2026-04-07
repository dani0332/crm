<?php

declare(strict_types=1);

use App\Exports\CarQuoteExport;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

test('car quote export headings include engagement level', function () {
    $headings = app(CarQuoteExport::class)->headings();

    expect($headings)->toContain('ENGAGEMENT LEVEL');
});
