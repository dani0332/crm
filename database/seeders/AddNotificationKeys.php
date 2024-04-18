<?php

namespace Database\Seeders;

use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class AddNotificationKeys extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $advisorNotificationTemplateId = ApplicationStorage::where('key_name', 'ADVISOR_NOTIFICATION_TEMPLATE')->count();
        if ($advisorNotificationTemplateId == 0) {
            $advisorNotificationTemplateId = ApplicationStorage::create([
                'key_name' => 'ADVISOR_NOTIFICATION_TEMPLATE',
                'value' => '603',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

    }
}
