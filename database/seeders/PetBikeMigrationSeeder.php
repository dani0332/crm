<?php

namespace Database\Seeders;

use App\Enums\QuoteTypeId;
use App\Models\BikeQuote;
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
        PetQuote::join('pet_quote_request_detail', 'pet_quote_request.id', '=', 'pet_quote_request_detail.pet_quote_request_id')
            ->whereNull('pet_quote_request.personal_quote_id')
            ->groupBy('pet_quote_request.id')
            ->chunk(100)->each(function ($petRecords){
                \DB::table('personal_quotes')->insert([
                    'quote_type_id' => QuoteTypeId::Pet,
                    'uuid' => $petRecords->uuid,
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
                    'updated_at' => $petRecords->updated_at
                ]);
            });

        // Fetch Old Pet Records from personal_quotes table and Dump into pet_quote_request table
        PersonalQuote::join('pet_quote_request', function ($petQuoteJoin){
                $petQuoteJoin->on('personal_quotes.uuid','=','pet_quote_request.uuid')
                    ->andOn('personal_quotes.code','=','pet_quote_request.code');
            })
            ->whereNull('pet_quote_request.personal_quote_id')
            ->groupBy('personal_quotes.uuid')
            ->chunk(100)->each(function ($personalQuoteRecords){
            \DB::table('pet_quote_request')->insert([
                'gender' => $personalQuoteRecords->gender,
                'address' => $personalQuoteRecords->address,
                'customer_id' => $personalQuoteRecords->customer_id,
                'lang' => $personalQuoteRecords->lang,
                'is_synced' => $personalQuoteRecords->is_synced,
                'additional_notes' => $personalQuoteRecords->additional_notes,
                'reviver_name' => $personalQuoteRecords->reviver_name,
                'promo_code' => $personalQuoteRecords->promo_code,
                'code' => $personalQuoteRecords->code,
                'no_of_pets_to_insure' => $personalQuoteRecords->no_of_pets_to_insure,
                'type_of_pet1' => $personalQuoteRecords->type_of_pet1,
                'age_of_pet1' => $personalQuoteRecords->age_of_pet1,
                'breed_of_pet1' => $personalQuoteRecords->breed_of_pet1,
                'type_of_pet2' => $personalQuoteRecords->type_of_pet2,
                'age_of_pet2' => $personalQuoteRecords->age_of_pet2,
                'breed_of_pet2' => $personalQuoteRecords->breed_of_pet2,
                'ilivein_accommodation_type_id' => $personalQuoteRecords->ilivein_accommodation_type_id,
                'iam_possession_type_id' => $personalQuoteRecords->iam_possession_type_id,
                'has_contents' => $personalQuoteRecords->has_contents,
                'contents_aed' => $personalQuoteRecords->contents_aed,
                'has_personal_belongings' => $personalQuoteRecords->has_personal_belongings,
                'personal_belongings_aed' => $personalQuoteRecords->personal_belongings_aed,
                'has_building' => $personalQuoteRecords->has_building,
                'building_aed' => $personalQuoteRecords->building_aed,
                'created_at' => $personalQuoteRecords->created_at,
                'updated_at' => $personalQuoteRecords->updated_at,
                'uuid' => $personalQuoteRecords->uuid,
                'insurer_quote_no' => $personalQuoteRecords->insurer_quote_no,
                'is_microchipped' => $personalQuoteRecords->is_microchipped,
                'microchip_no' => $personalQuoteRecords->microchip_no,
                'is_neutered' => $personalQuoteRecords->is_neutered,
                'is_mixed_breed' => $personalQuoteRecords->is_mixed_breed,
                'has_injury' => $personalQuoteRecords->has_injury,
                'previous_quote_id' => $personalQuoteRecords->previous_quote_id,
                'pa_id' => $personalQuoteRecords->pa_id,
                'parent_duplicate_quote_id' => $personalQuoteRecords->parent_duplicate_quote_id,
                'personal_quote_id' => $personalQuoteRecords->id,
                'pet_type_id' => $personalQuoteRecords->pet_type_id,
                'pet_age_id' => $personalQuoteRecords->pet_age_id,
            ]);
        });

        // Fetch data from pet_quote_details table and migrate into personal_quote_details table
        PersonalQuote::join('pet_quote_request', function ($petQuoteJoin){
            $petQuoteJoin->on('personal_quotes.uuid','=','pet_quote_request.uuid')
                ->andOn('personal_quotes.code','=','pet_quote_request.code');
            })
            ->join('pet_quote_request_detail', 'pet_quote_request.id', '=', 'pet_quote_request_detail.pet_quote_request_id')
            ->groupBy('personal_quotes.uuid')
            ->chunk(100)->each(function ($petRequestDetailsRecord){
                PersonalQuoteDetail::firstOrCreate([
                    'personal_quote_id' => $petRequestDetailsRecord->id,
                    'pa_id' => $petRequestDetailsRecord->pa_id,
                    'advisor_assigned_date' => $petRequestDetailsRecord->advisor_assigned_date,
                    'advisor_assigned_by_id' => $petRequestDetailsRecord->advisor_assigned_by_id,
                    'next_followup_date' => $petRequestDetailsRecord->next_followup_date,
                    'lost_reason_id' => $petRequestDetailsRecord->lost_reason_id,
                    'transapp_code' => $petRequestDetailsRecord->transapp_code,
                    'additional_notes' => $petRequestDetailsRecord->additional_notes,
                    'utm_source' => $petRequestDetailsRecord->utm_source,
                    'utm_medium' => $petRequestDetailsRecord->utm_medium,
                    'utm_campaign' => $petRequestDetailsRecord->utm_campaign,
                ]);
        });

        // Bike Data Migration
        // Fetch Old Bike Records from bike_quote_request table and Dump into personal_quote table
        BikeQuote::join('bike_quote_request_detail', 'bike_quote_request.id', '=', 'bike_quote_request_detail.bike_quote_request_id')
            ->whereNull('bike_quote_request.personal_quote_id')
            ->groupBy('bike_quote_request.id')
            ->chunk(100)->each(function ($bikeRecords){
                \DB::table('personal_quotes')->insert([
                    'quote_type_id' => QuoteTypeId::Bike,
                    'uuid' => $bikeRecords->uuid,
                    'code' => $bikeRecords->code,
                    'first_name' => $bikeRecords->first_name,
                    'last_name' => $bikeRecords->last_name,
                    'dob' => $bikeRecords->dob,
                    'nationality_id' => $bikeRecords->nationality_id,
                    'email' => $bikeRecords->email,
                    'mobile_no' => $bikeRecords->mobile_no,
                    'source' => $bikeRecords->source,
                    'currently_insured_with' => $bikeRecords->currently_insured_with,
                    'customer_id' => $bikeRecords->customer_id,
                    'reference_url' => $bikeRecords->reference_url,
                    'policy_number' => $bikeRecords->policy_number,
                    'advisor_id' => $bikeRecords->advisor_id,
                    'premium' => $bikeRecords->premium,
                    'renewal_batch' => $bikeRecords->renewal_batch,
                    'renewal_expiry_date' => $bikeRecords->renewal_expiry_date,
                    'previous_quote_policy_number' => $bikeRecords->previous_quote_policy_number,
                    'renewal_import_code' => $bikeRecords->renewal_import_code,
                    'previous_policy_expiry_date' => $bikeRecords->previous_policy_expiry_date,
                    'previous_quote_policy_premium' => $bikeRecords->previous_quote_policy_premium,
                    'policy_start_date' => $bikeRecords->policy_start_date,
                    'policy_issuance_date' => $bikeRecords->policy_issuance_date,
                    'payment_status_id' => $bikeRecords->payment_status_id,
                    'quote_status_id' => $bikeRecords->quote_status_id,
                    'notes' => $bikeRecords->notes,
                    'created_at' => $bikeRecords->created_at,
                    'updated_at' => $bikeRecords->updated_at
                ]);
        });

        // Fetch Old Bike Records from personal_quotes table and Migrate into bike_quote_request table
        PersonalQuote::join('bike_quote_request', function ($bikeQuoteJoin){
                $bikeQuoteJoin->on('personal_quotes.uuid','=','bike_quote_request.uuid')
                    ->andOn('personal_quotes.code','=','bike_quote_request.code');
            })
            ->whereNull('bike_quote_request.personal_quote_id')
            ->groupBy('personal_quotes.uuid')
            ->chunk(100)->each(function ($personalQuoteRecords){
                \DB::table('bike_quote_request')->insert([
                    'bike_company_to_insure' => $personalQuoteRecords->bike_company_to_insure,
                    'bike_value' => $personalQuoteRecords->bike_value,
                    'year_of_manufacture' => $personalQuoteRecords->year_of_manufacture,
                    'uae_license_held_for_id' => $personalQuoteRecords->uae_license_held_for_id,
                    'gender' => $personalQuoteRecords->gender,
                    'lang' => $personalQuoteRecords->lang,
                    'customer_id' => $personalQuoteRecords->customer_id,
                    'is_synced' => $personalQuoteRecords->is_synced,
                    'additional_notes' => $personalQuoteRecords->additional_notes,
                    'reviver_name' => $personalQuoteRecords->reviver_name,
                    'promo_code' => $personalQuoteRecords->promo_code,
                    'code' => $personalQuoteRecords->code,
                    'uuid' => $personalQuoteRecords->uuid,
                    'previous_quote_id' => $personalQuoteRecords->previous_quote_id,
                    'pa_id' => $personalQuoteRecords->pa_id,
                    'other_email_addresses' => $personalQuoteRecords->pet_type_id,
                    'previous_advisor_id' => $personalQuoteRecords->pet_age_id,
                    'personal_quote_id' => $personalQuoteRecords->id,
                ]);
        });

        // Fetch data from pet_quote_details table and migrate into personal_quote_details table
        PersonalQuote::join('bike_quote_request', function ($bikeQuoteJoin){
                $bikeQuoteJoin->on('personal_quotes.uuid','=','bike_quote_request.uuid')
                    ->andOn('personal_quotes.code','=','bike_quote_request.code');
            })
            ->join('bike_quote_request_detail', 'bike_quote_request.id', '=', 'bike_quote_request_detail.bike_quote_request_id')
            ->groupBy('personal_quotes.uuid')
            ->chunk(100)->each(function ($bikeRequestDetailsRecord){
                PersonalQuoteDetail::firstOrCreate([
                    'personal_quote_id' => $bikeRequestDetailsRecord->id,
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
            });
    }
}
