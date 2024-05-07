<?php

namespace Database\Seeders;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddDttFlagApplicationStorage extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $flag = ApplicationStorage::where('key_name', ApplicationStorageEnums::DTT_ENABLED)->get();
        if (count($flag) == 0) {
            DB::table('application_storage')->insert([[
                'key_name' => ApplicationStorageEnums::DTT_ENABLED,
                'value' => '0',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]]);
        }
        $value = ApplicationStorage::where('key_name', ApplicationStorageEnums::DTT_ADVISOR)->get();
        if (count($value) == 0) {
            DB::table('application_storage')->insert([[
                'key_name' => ApplicationStorageEnums::DTT_ADVISOR,
                'value' => implode(',', ['name' => 'Alfred', 'email' => 'askalfred@insurancemarket.ae']),
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]]);
        }
        $dttReplyToProd = ApplicationStorage::where('key_name', ApplicationStorageEnums::DTT_REPLY_TO)->get();
        if (count($dttReplyToProd) == 0) {
            DB::table('application_storage')->insert([[
                'key_name' => ApplicationStorageEnums::DTT_REPLY_TO,
                'value' => 'buy@insurancemarket.ae',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]]);
        }
    }
}
