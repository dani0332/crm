<?php

namespace Database\Seeders;

use App\Models\ClaimHistory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class AddLOBsClaimHistoryOptionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // update the existing claim history record with the code if required
        $this->updateExistingRecord();

        $codeToInsert = 'FIRST_BIKE_EVER'; // write the code you want to insert here

        try {
            // check if record with the code already exists
            if (ClaimHistory::where('code', $codeToInsert)->exists()) {
                Log::info("Skipping ClaimHistory record insertion: Code '{$codeToInsert}' already exists.");

                return;
            }

            // create a new ClaimHistory instance with your desired data
            $claimHistory = new ClaimHistory([
                'code' => $codeToInsert,
                'text' => 'This is my very first bike ever!',
                'sort_order' => 8,
                'quote_type_id' => 6, // quote type id for bike LOB
            ]);

            // save the new claim history record
            $claimHistory->save();

            Log::info("ClaimHistory record with code '{$codeToInsert}' inserted successfully.");
        } catch (\Exception $exception) {
            Log::error('Error inserting ClaimHistory record: '.$exception->getMessage());
        }
    }

    private function updateExistingRecord()
    {
        $codeToUpdate = 'FIRST_CAR_EVER';
        $updatedQuoteTypeId = 1; // car quote type id

        try {
            // attempt to find existing record by code
            $claimHistory = ClaimHistory::where('code', $codeToUpdate)->first();

            if ($claimHistory) {
                // check if update is necessary
                if ($claimHistory->quote_type_id !== $updatedQuoteTypeId) {
                    $claimHistory->quote_type_id = $updatedQuoteTypeId;
                    $claimHistory->save();

                    Log::info("ClaimHistory record with code '{$codeToUpdate}' updated successfully.");
                } else {
                    Log::info("ClaimHistory record with code '{$codeToUpdate}' already has quote_type_id set to {$updatedQuoteTypeId}. Skipping update.");
                }

                return; // exit if record is found and handled (update or skip)
            }
        } catch (\Exception $exception) {
            Log::error('Error updating ClaimHistory record: '.$exception->getMessage());
        }
    }
}
