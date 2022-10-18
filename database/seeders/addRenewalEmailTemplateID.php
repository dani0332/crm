<?php

namespace Database\Seeders;

use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class addRenewalEmailTemplateID extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $renewalEmailTemplateID = ApplicationStorage::where('key_name', 'CAR_RENEWAL_ALLOCATION_LEAD_EMAIL_TEMPLATE_ID')->first();
        if ($renewalEmailTemplateID == null) {
            ApplicationStorage::insert([
                'key_name' => 'CAR_RENEWAL_ALLOCATION_LEAD_EMAIL_TEMPLATE_ID',
                'value' => 390,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
