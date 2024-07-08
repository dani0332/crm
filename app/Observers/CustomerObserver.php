<?php

namespace App\Observers;

use App\Models\Customer;
use App\Jobs\SyncCustomerJob;

class CustomerObserver
{
    public function created(Customer $customer): void
    {
        SyncCustomerJob::dispatch($customer->id, $customer->email);
    }
}
