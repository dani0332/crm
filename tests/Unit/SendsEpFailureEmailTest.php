<?php

use App\Traits\SendsEpFailureEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Helpers\TestSchemaCreator;

/**
 * @return object{sendEpFailureEmailForTest: callable}
 */
function createSendsEpFailureEmailSender(): object
{
    return new class
    {
        use SendsEpFailureEmail;

        public function sendEpFailureEmailForTest(int $quoteId, int $quoteTypeId, int $etId, string $logPrefix, bool $isSageBooking = false): void
        {
            $this->sendEpFailureEmail($quoteId, $quoteTypeId, $etId, $logPrefix, $isSageBooking);
        }
    };
}

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

test('sendEpFailureEmail does not throw when embedded transaction lookup fails', function () {
    Mail::fake();

    DB::disconnect();

    $sender = createSendsEpFailureEmailSender();

    expect(fn () => $sender->sendEpFailureEmailForTest(1, 1, 42, 'test:', true))
        ->not->toThrow(Throwable::class);

    Mail::assertNothingSent();
});
