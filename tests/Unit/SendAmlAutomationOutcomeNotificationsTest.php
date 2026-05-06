<?php

declare(strict_types=1);

use App\Enums\QuoteTypes;
use App\Listeners\SendAmlAutomationOutcomeNotifications;
use Illuminate\Database\Eloquent\Model;

it('returns null when getQuoteObject resolves to false', function () {
    $listener = new class extends SendAmlAutomationOutcomeNotifications
    {
        public function getQuoteObject($quoteType, $id)
        {
            return false;
        }
    };

    $method = new ReflectionMethod(SendAmlAutomationOutcomeNotifications::class, 'resolveQuoteForAutomationOutcome');
    $method->setAccessible(true);

    expect($method->invoke($listener, QuoteTypes::SAVINGS, 1))->toBeNull();
});

it('returns resolved eloquent model and loads advisor', function () {
    $quote = Mockery::mock(Model::class)->makePartial();
    $quote->shouldReceive('loadMissing')->once()->with('advisor');

    $listener = new class($quote) extends SendAmlAutomationOutcomeNotifications
    {
        public function __construct(private $quote) {}

        public function getQuoteObject($quoteType, $id)
        {
            return $this->quote;
        }
    };

    $method = new ReflectionMethod(SendAmlAutomationOutcomeNotifications::class, 'resolveQuoteForAutomationOutcome');
    $method->setAccessible(true);

    $resolved = $method->invoke($listener, QuoteTypes::TRAVEL, 1);

    expect($resolved)->toBe($quote);
});
