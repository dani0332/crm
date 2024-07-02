<?php

namespace App\Listeners;

use App\Enums\GenericRequestEnum;
use App\Events\QuoteEmailUpdated;
use App\Models\Customer;
use App\Services\CustomerService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class UpdateCustomerEmail implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(QuoteEmailUpdated $event)
    {
        // try {
        //     $quote = $event->quote;
        //     $customer = Customer::find($quote->customer_id);

        //     if ($customer && trim($customer->email) !== trim($quote->email)) {
        //         app(CustomerService::class)->makeAdditionalContactPrimary($quote, GenericRequestEnum::EMAIL, trim($quote->email));
        //     }
        // } catch (Exception $e) {
        //     Log::error('Update Customer Email Failed - '.$e->getMessage());
        // }
    }

    public function shouldQueue(QuoteEmailUpdated $event): bool
    {
        $customer = Customer::find($event->quote->customer_id);
        info('triggered should queue');
        if ($customer && trim($customer->email) !== trim($event->quote->email)) {
            info('triggered should queue true');

            return true;
        }
        info('triggered should queue false');

        return false;
    }
}
