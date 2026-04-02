<?php

declare(strict_types=1);

use App\Services\TravelQuoteService;

test('travel member delete blocked message is stable for clients', function () {
    expect(app(TravelQuoteService::class)->travelMemberDeleteBlockedMessage())
        ->toBe('This member can only be removed when their linked payment is cancelled, failed, or declined.');
});
