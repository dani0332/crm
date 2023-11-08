<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UpdateCodeColumnInCustomerTable extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Customer::whereNull('code')->chunk(100, function ($customers) {
            foreach ($customers as $customer) {

                if (! empty($customer->code)) {
                    continue;
                }

                $customer->update([
                    'code' => CustomerTypeEnum::IndividualShort.'-'.$customer->id
                ]);
            }
        });
    }
}
