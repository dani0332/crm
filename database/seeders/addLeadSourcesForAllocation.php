<?php

namespace Database\Seeders;

use App\Models\LeadSource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class addLeadSourcesForAllocation extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $leadSources = LeadSource::all()->count();
        if ($leadSources == 0) {
            DB::table('lead_sources')->insert([[
                'name' => 'TPL_COMP',
                'code' => 'TPL_C',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ], [
                'name' => 'TPL_RENEWALS',
                'code' => 'TPL_R',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]]);
        }
    }
}
