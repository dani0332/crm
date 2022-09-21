<?php

namespace Database\Seeders;

use App\Models\CustomerAdditionalContact;
use App\Models\CustomerAdditionalInfo;
use Illuminate\Database\Seeder;

class CustomerAdditionalContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $customerAdditionalContact = CustomerAdditionalContact::all()->count();

        if ($customerAdditionalContact == 0) {
            $oldCustomerAdditionalInfo = CustomerAdditionalInfo::all();
            foreach ($oldCustomerAdditionalInfo as $info) {
                if ($info->email_address &&
                    ! CustomerAdditionalContact::where('key', 'email')->where('value', $info->email_address)->first()) {
                    CustomerAdditionalContact::create([
                        'customer_id' => $info->customer_id,
                        'key' => 'email',
                        'value' => $info->email_address,
                    ]);
                }
                if ($info->mobile_no &&
                    ! CustomerAdditionalContact::where('key', 'mobile_no')->where('value', $info->mobile_no)->first()) {
                    CustomerAdditionalContact::create([
                        'customer_id' => $info->customer_id,
                        'key' => 'mobile_no',
                        'value' => $info->mobile_no,
                    ]);
                }
            }
        }
    }
}
