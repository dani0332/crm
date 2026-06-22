<?php

declare(strict_types=1);

use App\Console\Commands\CallBackNotification;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;

describe('CallBackNotification (Instant Alfred reminders)', function () {
    test('allows Device quote type id for scheduled reminders', function () {
        $command = new CallBackNotification;
        $ref = new ReflectionClass($command);
        $prop = $ref->getProperty('allowedQuoteTypeIds');
        $prop->setAccessible(true);

        expect($prop->getValue($command))->toContain(QuoteTypeId::Device);
    });

    test('isAllowedQuoteType accepts Device', function () {
        $command = new CallBackNotification;
        $method = (new ReflectionClass($command))->getMethod('isAllowedQuoteType');
        $method->setAccessible(true);

        expect($method->invoke($command, quoteTypeCode::Device))->toBeTrue();
    });
});
