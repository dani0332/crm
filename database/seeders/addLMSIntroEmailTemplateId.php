<?php

namespace Database\Seeders;

use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class addLMSIntroEmailTemplateId extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $introEmailTemplateIdCount = ApplicationStorage::where('key_name', 'LMS_INTRO_EMAIL_TEMPLATE_ID')->first();
        if ($introEmailTemplateIdCount == null) {
            ApplicationStorage::insert([
                'key_name' => 'LMS_INTRO_EMAIL_TEMPLATE_ID',
                'value' => 426,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }

        $reassignEmailTemplateIdCount = ApplicationStorage::where('key_name', 'LMS_REASSIGN_EMAIL_TEMPLATE_ID')->first();
        if ($reassignEmailTemplateIdCount == null) {
            ApplicationStorage::insert([
                'key_name' => 'LMS_REASSIGN_EMAIL_TEMPLATE_ID',
                'value' => 428,
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }
    }
}
