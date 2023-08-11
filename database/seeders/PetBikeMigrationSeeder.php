<?php

namespace Database\Seeders;

use App\Enums\QuoteTypeId;
use App\Models\BikeQuote;
use App\Models\InsuranceProvider;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use App\Models\PetQuote;
use Illuminate\Database\Seeder;

class PetBikeMigrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        // Pet Data Migration
        // Fetch Old Pet Records from pet_quote_request table and Dump into personal_quote table

        PetQuote::leftJoin('pet_quote_request_detail', 'pet_quote_request.id', '=', 'pet_quote_request_detail.pet_quote_request_id')
            //->whereNull('pet_quote_request.personal_quote_id')
            ->whereNotNull('pet_quote_request.code')
            //->groupBy('pet_quote_request.id')
            ->chunk(100, function ($petChunkRecords) {
                foreach ($petChunkRecords as $petRecords) {
                    $quote = null;
                    $quote = PersonalQuote::firstOrCreate(['uuid' => trim($petRecords->uuid)],
                        [
                            'quote_type_id' => QuoteTypeId::Pet,
                            'code' => $petRecords->code,
                            'first_name' => $petRecords->first_name,
                            'last_name' => $petRecords->last_name,
                            'dob' => $petRecords->dob,
                            'nationality_id' => $petRecords->nationality_id,
                            'email' => $petRecords->email,
                            'mobile_no' => $petRecords->mobile_no,
                            'source' => $petRecords->source,
                            'customer_id' => $petRecords->customer_id,
                            'device' => $petRecords->device,
                            'reference_url' => $petRecords->reference_url,
                            'policy_number' => $petRecords->policy_number,
                            'advisor_id' => $petRecords->advisor_id,
                            'premium' => $petRecords->premium,
                            'renewal_batch' => $petRecords->renewal_batch,
                            'renewal_expiry_date' => $petRecords->renewal_expiry_date,
                            'previous_quote_policy_number' => $petRecords->previous_quote_policy_number,
                            'renewal_import_code' => $petRecords->renewal_import_code,
                            'previous_policy_expiry_date' => $petRecords->previous_policy_expiry_date,
                            'previous_quote_policy_premium' => $petRecords->previous_quote_policy_premium,
                            'policy_start_date' => $petRecords->policy_start_date,
                            'policy_issuance_date' => $petRecords->policy_issuance_date,
                            'payment_status_id' => $petRecords->payment_status_id,
                            'quote_status_id' => $petRecords->quote_status_id,
                            'notes' => $petRecords->notes,
                            'created_at' => $petRecords->created_at,
                            'updated_at' => $petRecords->updated_at,
                        ]);

                    $petRecords->personal_quote_id = $quote->id;
                    $petRecords->save();
                }
            });

        // Fetch data from pet_quote_details table and migrate into personal_quote_details table
        PersonalQuote::join('pet_quote_request', function ($petQuoteJoin) {
            $petQuoteJoin->on('personal_quotes.uuid', '=', 'pet_quote_request.uuid');
        })
            ->join('pet_quote_request_detail', 'pet_quote_request.id', '=', 'pet_quote_request_detail.pet_quote_request_id')
            ->groupBy('personal_quotes.uuid')
            ->chunk(100, function ($petRequestDetailsRecord) {
                foreach ($petRequestDetailsRecord as $petRequestDetailRecord) {
                    PersonalQuoteDetail::firstOrCreate(['personal_quote_id' => $petRequestDetailRecord->id],
                        [
                            'pa_id' => $petRequestDetailRecord->pa_id,
                            'advisor_assigned_date' => $petRequestDetailRecord->advisor_assigned_date,
                            'advisor_assigned_by_id' => $petRequestDetailRecord->advisor_assigned_by_id,
                            'next_followup_date' => $petRequestDetailRecord->next_followup_date,
                            'lost_reason_id' => $petRequestDetailRecord->lost_reason_id,
                            'transapp_code' => $petRequestDetailRecord->transapp_code,
                            'additional_notes' => $petRequestDetailRecord->additional_notes,
                            'utm_source' => $petRequestDetailRecord->utm_source,
                            'utm_medium' => $petRequestDetailRecord->utm_medium,
                            'utm_campaign' => $petRequestDetailRecord->utm_campaign,
                        ]);
                }
            });

        // Bike Data Migration
        // Fetch Old Bike Records from bike_quote_request table and Dump into personal_quote table
        BikeQuote::leftJoin('bike_quote_request_detail', 'bike_quote_request.id', '=', 'bike_quote_request_detail.bike_quote_request_id')
           // ->whereNull('bike_quote_request.personal_quote_id')
            ->whereNotNull('bike_quote_request.code')
            ->groupBy('bike_quote_request.id')
            ->chunk(100, function ($bikeRecords) {
                foreach ($bikeRecords as $bikeRecord) {

                    $insurer = InsuranceProvider::where('code', $bikeRecord->currently_insured_with)->first();

                    $quote = PersonalQuote::firstOrCreate(['uuid' => $bikeRecord->uuid],
                        [
                            'quote_type_id' => QuoteTypeId::Bike,
                            'code' => $bikeRecord->code,
                            'first_name' => $bikeRecord->first_name,
                            'last_name' => $bikeRecord->last_name,
                            'dob' => $bikeRecord->dob,
                            'nationality_id' => $bikeRecord->nationality_id,
                            'email' => $bikeRecord->email,
                            'mobile_no' => $bikeRecord->mobile_no,
                            'source' => $bikeRecord->source,
                            'currently_insured_with_id' => $insurer->id ?? null,
                            'customer_id' => $bikeRecord->customer_id,
                            'reference_url' => $bikeRecord->reference_url,
                            'policy_number' => $bikeRecord->policy_number,
                            'advisor_id' => $bikeRecord->advisor_id,
                            'premium' => $bikeRecord->premium,
                            'renewal_batch' => $bikeRecord->renewal_batch,
                            'renewal_expiry_date' => $bikeRecord->renewal_expiry_date,
                            'previous_quote_policy_number' => $bikeRecord->previous_quote_policy_number,
                            'renewal_import_code' => $bikeRecord->renewal_import_code,
                            'previous_policy_expiry_date' => $bikeRecord->previous_policy_expiry_date,
                            'previous_quote_policy_premium' => $bikeRecord->previous_quote_policy_premium,
                            'policy_start_date' => $bikeRecord->policy_start_date,
                            'policy_issuance_date' => $bikeRecord->policy_issuance_date,
                            'payment_status_id' => $bikeRecord->payment_status_id,
                            'quote_status_id' => $bikeRecord->quote_status_id,
                            'notes' => $bikeRecord->notes,
                            'created_at' => $bikeRecord->created_at,
                            'updated_at' => $bikeRecord->updated_at,
                        ]);

                    $bikeRecord->personal_quote_id = $quote->id;
                    $bikeRecord->save();
                }
            });

        // Fetch data from pet_quote_details table and migrate into personal_quote_details table
        PersonalQuote::join('bike_quote_request', function ($bikeQuoteJoin) {
            $bikeQuoteJoin->on('personal_quotes.uuid', '=', 'bike_quote_request.uuid');
        })
            ->join('bike_quote_request_detail', 'bike_quote_request.id', '=', 'bike_quote_request_detail.bike_quote_request_id')
            ->groupBy('personal_quotes.uuid')
            ->chunk(100, function ($bikeRequestDetailsRecords) {
                foreach ($bikeRequestDetailsRecords as $bikeRequestDetailsRecord) {
                    PersonalQuoteDetail::firstOrCreate(['personal_quote_id' => $bikeRequestDetailsRecord->id], [
                        'pa_id' => $bikeRequestDetailsRecord->pa_id,
                        'advisor_assigned_date' => $bikeRequestDetailsRecord->advisor_assigned_date,
                        'advisor_assigned_by_id' => $bikeRequestDetailsRecord->advisor_assigned_by_id,
                        'next_followup_date' => $bikeRequestDetailsRecord->next_followup_date,
                        'lost_reason_id' => $bikeRequestDetailsRecord->lost_reason_id,
                        'transapp_code' => $bikeRequestDetailsRecord->transapp_code,
                        'additional_notes' => $bikeRequestDetailsRecord->additional_notes,
                        'utm_source' => $bikeRequestDetailsRecord->utm_source,
                        'utm_medium' => $bikeRequestDetailsRecord->utm_medium,
                        'utm_campaign' => $bikeRequestDetailsRecord->utm_campaign,
                    ]);
                }
            });
    }
}
