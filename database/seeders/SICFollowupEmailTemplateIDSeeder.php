<?php

namespace Database\Seeders;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SICFollowupEmailTemplateIDSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $sicFollowupEmailTempID = ApplicationStorage::where('key_name', ApplicationStorageEnums::SIC_FOLLOWUP_TEMPLATE_ID)->first();
        if (empty($sicFollowupEmailTempID)) {
            DB::table('application_storage')->insert([
                'key_name' => ApplicationStorageEnums::SIC_FOLLOWUP_TEMPLATE_ID,
                'value' => 678,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

}
