<?php

declare(strict_types=1);

use App\Models\TravelQuote;
use App\Repositories\AuditRepository;

describe('AuditRepository quote type normalization', function () {
    test('resolves quote model for lowercase lob token', function () {
        $quoteObject = AuditRepository::resolveQuoteObjectForAuditRequest('travel');

        expect($quoteObject)->toBeInstanceOf(TravelQuote::class);
    });

    test('keeps canonical casing for multi-word lob tokens', function () {
        $reflection = new ReflectionClass(AuditRepository::class);
        $method = $reflection->getMethod('normalizeQuoteTypeToken');

        $normalized = $method->invoke(
            null,
            'homeappliance',
            ['CorpLine', 'HomeAppliance']
        );

        expect($normalized)->toBe('HomeAppliance');
    });
});
