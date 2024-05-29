<?php

namespace Database\Seeders;

use App\Enums\LeadSourceEnum;
use App\Models\LeadSource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DubaiLeadSource extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $leadSourcesCount = LeadSource::where('code', LeadSourceEnum::DUBAI_NOW)->count();
        if ($leadSourcesCount == 0) {
            DB::table('lead_sources')->insert(
                [
                    'name' => LeadSourceEnum::DUBAI_NOW,
                    'code' => LeadSourceEnum::DUBAI_NOW,
                    'is_active' => 1,
                    'is_applicable_for_rules' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
