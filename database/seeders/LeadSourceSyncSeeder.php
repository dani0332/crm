<?php

namespace Database\Seeders;

use App\Models\HealthQuote;
use App\Models\LeadSource;
use Illuminate\Database\Seeder;

class LeadSourceSyncSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        HealthQuote::query()
            ->select('source')
            ->whereNotNull('source')
            ->groupBy('source')
            ->chunk(500, function ($quotes) {
                foreach ($quotes as $quotes) {
                    $source = $quotes->source;
                    $leadSource = LeadSource::where('name', $source)
                        ->first();
                    // sync new lead sources
                    if (! $leadSource) {
                        $leadSource = new LeadSource();
                        $leadSource->name = $source;
                        $leadSource->is_active = 1;
                        $leadSource->is_applicable_for_rules = 0;
                        $leadSource->save();
                    }
                }
            });
    }
}
