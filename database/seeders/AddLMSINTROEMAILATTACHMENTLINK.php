<?php

namespace Database\Seeders;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class AddLMSINTROEMAILATTACHMENTLINK extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $introEmailAttachmentLink = ApplicationStorage::where('key_name', ApplicationStorageEnums::LMS_INTRO_EMAIL_ATTACHMENT_URL)->first();
        if ($introEmailAttachmentLink == null) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::LMS_INTRO_EMAIL_ATTACHMENT_URL,
                'value' => '',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }
    }
}
