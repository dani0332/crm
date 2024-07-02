<?php

namespace App\Listeners;

use App\Enums\GenericRequestEnum;
use App\Events\QuoteEmailUpdated;
use App\Models\Customer;
use App\Services\CustomerService;
use Exception;
use Illuminate\Support\Facades\Log;

class UpdateCustomerEmail
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
}
