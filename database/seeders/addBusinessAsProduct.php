<?php

namespace Database\Seeders;

use App\Enums\quoteTypeCode;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class addBusinessAsProduct extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $corpLineProduct = Team::where('name', quoteTypeCode::CORPLINE)->first();
        if (! $corpLineProduct) {
            DB::table('teams')->insert([
                'name' => quoteTypeCode::CORPLINE,
                'is_active' => 1,
                'type' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $groupMedicalProduct = Team::where('name', quoteTypeCode::GroupMedical)->first();
        if (! $groupMedicalProduct) {
            DB::table('teams')->insert([
                'name' => quoteTypeCode::GroupMedical,
                'is_active' => 1,
                'type' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
