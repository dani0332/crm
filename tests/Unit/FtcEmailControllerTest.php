<?php

use App\Enums\QuoteTypes;
use App\Http\Controllers\FtcEmailController;
use App\Jobs\SendFTCEmailJob;
use Illuminate\Support\Facades\Bus;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

dataset('ftc_allowed_quote_types', [
    QuoteTypes::CAR->value,
    QuoteTypes::HOME->value,
    QuoteTypes::HEALTH->value,
    QuoteTypes::LIFE->value,
    QuoteTypes::BUSINESS->value,
    QuoteTypes::BIKE->value,
    QuoteTypes::YACHT->value,
    QuoteTypes::TRAVEL->value,
    QuoteTypes::PET->value,
    QuoteTypes::CYCLE->value,
    QuoteTypes::JETSKI->value,
    QuoteTypes::SAVINGS->value,
]);

it('dispatches ftc email job for allowed quote types', function (string $quoteType) {
    Bus::fake();

    $controller = app(FtcEmailController::class);
    $response = $controller->send($quoteType, 'uuid-123');

    $payload = $response->getData(true);

    expect($payload['queued'])->toBeTrue();
    Bus::assertDispatched(SendFTCEmailJob::class);
})->with('ftc_allowed_quote_types');

it('rejects disallowed quote types', function () {
    Bus::fake();

    $controller = app(FtcEmailController::class);

    expect(fn () => $controller->send('Group Medical', 'uuid-123'))
        ->toThrow(NotFoundHttpException::class);

    Bus::assertNotDispatched(SendFTCEmailJob::class);
});
