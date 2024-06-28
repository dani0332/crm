<?php

namespace App\Listeners;

use App\Events\QuoteEmailUpdated;
use App\Models\Customer;

class UpdateCustomerEmail
{
    /**
     * Handle the event.
     *
     */
    public function handle(QuoteEmailUpdated $event)
    {
        $quote = $event->quote;

        $customer = Customer::find($quote->customer_id);

        if ($customer && $customer->email !== $quote->email) {
            $customer->email = $quote->email;
            $customer->save();
        }
    }
}
