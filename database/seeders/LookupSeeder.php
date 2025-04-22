<?php

namespace Database\Seeders;

use App\Enums\LookupsEnum;
use App\Models\Lookup;
use Illuminate\Database\Seeder;

class LookupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->sendUpdateCancelOptions();
    }

    private function sendUpdateCancelOptions(): void
    {
        Lookup::firstOrCreate([
            'key' => LookupsEnum::SEND_UPDATE_CANCEL_OPTIONS,
            'code' => 'requested-by-mistake',
            'text' => 'Requested by mistake',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'key' => LookupsEnum::SEND_UPDATE_CANCEL_OPTIONS,
            'code' => 'selected-wrong-update',
            'text' => 'Selected the wrong update',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Lookup::firstOrCreate([
            'key' => LookupsEnum::SEND_UPDATE_CANCEL_OPTIONS,
            'code' => 'update-no-longer-needed',
            'text' => 'Update no longer needed',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
