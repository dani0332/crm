<?php

namespace App\Listeners;

use App\Events\QuoteEmailUpdated;
use App\Models\Customer;
use Exception;
use Illuminate\Support\Facades\DB;

class UpdateCustomerEmail
{
    /**
     * Handle the event.
     */
    public function handle(QuoteEmailUpdated $event)
    {
        $quote = $event->quote;

        DB::beginTransaction();

        try {
            $customer = Customer::find($quote->customer_id);

            if ($customer && $customer->email !== $quote->email) {
                $customer->email = $quote->email;
                $customer->save();
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            info('Update Customer Email Failed');
        }
    }
}
