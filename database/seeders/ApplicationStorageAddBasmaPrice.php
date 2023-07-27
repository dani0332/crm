<?php

namespace Database\Seeders;

use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class ApplicationStorageAddBasmaPrice extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ApplicationStorage::firstOrCreate([
            'key_name' => 'BASMA_PRICE',
            'value' => 37,
            'is_active' => 1,
        ],[
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
