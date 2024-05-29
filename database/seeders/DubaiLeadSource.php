<?php

namespace Database\Seeders;

use App\Enums\LeadSourceEnum;
use App\Models\LeadSource;
use App\Models\PersonalQuote;
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

        $this->addMissingLeadSource();
    }

    private function addMissingLeadSource()
    {
        // Step 1: Retrieve distinct sources from personal_quotes
        $leadSourcesPersonalQuotes = PersonalQuote::select('source')
            ->whereNotNull('source')
            ->groupBy('source')
            ->get()
            ->pluck('source')
            ->toArray();

        // Step 2: Retrieve existing sources from lead_sources
        $existingLeadSources = LeadSource::select('name')->pluck('name')->toArray();

        // Step 3: Find the differences between the two arrays
        $newLeadSources = array_diff($leadSourcesPersonalQuotes, $existingLeadSources);

        // Step 4: Prepare data for insertion
        $dataToInsert = array_map(function ($source) {
            return [
                'name' => $source,
                'is_active' => 1,
                'is_applicable_for_rules' => 0,
            ];
        }, $newLeadSources);

        if (!empty($dataToInsert)) {
            // Step 5: Insert new lead sources in a single query
            LeadSource::insert($dataToInsert);
        }
    }
}
