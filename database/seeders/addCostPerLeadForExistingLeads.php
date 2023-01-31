<?php

namespace Database\Seeders;

use App\Models\CarQuote;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class addCostPerLeadForExistingLeads extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $costPerLeadCount = CarQuote::whereNotNull('cost_per_lead')->count();
        if ($costPerLeadCount == 0) {
            DB::statement('UPDATE car_quote_request SET cost_per_lead = (SELECT cost_per_lead FROM tiers WHERE id = car_quote_request.tier_id) WHERE tier_id IS NOT NULL');
        }
    }
}
