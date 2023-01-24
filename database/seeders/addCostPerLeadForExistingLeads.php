<?php

namespace Database\Seeders;

use App\Models\CarQuote;
use Illuminate\Database\Seeder;

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
            CarQuote::whereNotNull('tier_id')
            ->update(['cost_per_lead' => function ($query) {
                $query->from('tiers')
                    ->select('cost_per_lead')
                    ->whereColumn('id', 'car_quote_request.tier_id');
            }]);
        }
    }
}
