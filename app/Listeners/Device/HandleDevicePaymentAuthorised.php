<?php

declare(strict_types=1);

namespace App\Listeners\Device;

use App\Enums\QuoteTypes;
use App\Events\Device\DevicePaymentAuthorised;
use App\Jobs\SendFTCEmailJob;
use App\Services\Logger\LoggerService;

class HandleDevicePaymentAuthorised
{
    /**
     * Handle the DevicePaymentAuthorised event.
     */
    public function handle(DevicePaymentAuthorised $event): void
    {
        $quote = $event->quote;

        LoggerService::info('DeviceQuoteObserver: Payment authorized, dispatching FTC email', [
            'quote_uuid' => $quote->uuid,
            'quote_code' => $quote->code,
        ]);

        SendFTCEmailJob::dispatch($quote->uuid, QuoteTypes::DEVICE);
    }
}
