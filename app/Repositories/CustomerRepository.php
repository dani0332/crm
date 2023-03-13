<?php

namespace App\Repositories;

use App\Models\Customer;

class CustomerRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return Customer::class;
    }

    /**
     * @param $customerId
     * @param $data
     * @return bool
     */
    public function fetchStoreAdditionalContact($customerId, $data)
    {
        $customer = $this->findOrFail($customerId);
        $customer->additionalContactInfo()->create($data);

        return $customer;
    }
}
