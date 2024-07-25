<?php

namespace Database\Seeders;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AlfredProtectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $alfredProtectTemplate = ApplicationStorage::where('key_name', ApplicationStorageEnums::ALFRED_PROTECT_BOOK_POLICY_TEMPLATE)->first();
        if ($alfredProtectTemplate == null) {
            ApplicationStorage::insert([
                'key_name' => ApplicationStorageEnums::ALFRED_PROTECT_BOOK_POLICY_TEMPLATE,
                'value' => '702',
                'created_at' => now(),
                'updated_at' => now(),
                'is_active' => 1,
            ]);
        }
    }
}
