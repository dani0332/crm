<?php

namespace App\Listeners;

use App\Events\QuoteEmailUpdated;
use App\Models\Customer;
use Exception;
use Illuminate\Support\Facades\Log;

class UpdateCustomerEmail
{
    /**
     * Handle the event.
     */
    public function handle(QuoteEmailUpdated $event)
    {
        $quote = $event->quote;

        // try {
        //     $customer = Customer::find($quote->customer_id);

        //     if ($customer && trim($customer->email) !== trim($quote->email)) {
        //         $customer->email = trim($quote->email);
        //         $customer->save();
        //     }
        // } catch (Exception $e) {
        //     Log::error('Update Customer Email Failed - '.$e->getMessage());
        // }
    }
}
