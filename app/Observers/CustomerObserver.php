<?php

namespace App\Observers;

use App\Jobs\SyncCustomerJob;
use App\Models\Customer;

class CustomerObserver
{
    public function created(Customer $customer): void
    {
        SyncCustomerJob::dispatch($customer->id, $customer->email);
    }
}
