<?php

namespace Database\Seeders;

use App\Enums\QuoteTypeId;
use App\Http\Requests\BikeQuoteRequest;
use App\Http\Requests\PetQuoteRequest;
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
            ->chunk(100, function ($petChunkRecords){
                foreach ($petChunkRecords as $petRecords){
                    PersonalQuote::firstOrCreate([
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
                }
            });

        // Fetch Old Pet Records from personal_quotes table and Dump into pet_quote_request table
        PersonalQuote::join('pet_quote_request', function ($petQuoteJoin){
                $petQuoteJoin->on('personal_quotes.uuid','=','pet_quote_request.uuid')
                    ->andOn('personal_quotes.code','=','pet_quote_request.code');
            })
            ->whereNull('pet_quote_request.personal_quote_id')
            ->groupBy('personal_quotes.uuid')
            ->chunk(100, function ($personalQuoteRecords){
                foreach ($personalQuoteRecords as $personalQuoteRecord){
                    PetQuoteRequest::firstOrCreate([
                        'gender' => $personalQuoteRecord->gender,
                        'address' => $personalQuoteRecord->address,
                        'customer_id' => $personalQuoteRecord->customer_id,
                        'lang' => $personalQuoteRecord->lang,
                        'is_synced' => $personalQuoteRecord->is_synced,
                        'additional_notes' => $personalQuoteRecord->additional_notes,
                        'reviver_name' => $personalQuoteRecord->reviver_name,
                        'promo_code' => $personalQuoteRecord->promo_code,
                        'code' => $personalQuoteRecord->code,
                        'no_of_pets_to_insure' => $personalQuoteRecord->no_of_pets_to_insure,
                        'type_of_pet1' => $personalQuoteRecord->type_of_pet1,
                        'age_of_pet1' => $personalQuoteRecord->age_of_pet1,
                        'breed_of_pet1' => $personalQuoteRecord->breed_of_pet1,
                        'type_of_pet2' => $personalQuoteRecord->type_of_pet2,
                        'age_of_pet2' => $personalQuoteRecord->age_of_pet2,
                        'breed_of_pet2' => $personalQuoteRecord->breed_of_pet2,
                        'ilivein_accommodation_type_id' => $personalQuoteRecord->ilivein_accommodation_type_id,
                        'iam_possession_type_id' => $personalQuoteRecord->iam_possession_type_id,
                        'has_contents' => $personalQuoteRecord->has_contents,
                        'contents_aed' => $personalQuoteRecord->contents_aed,
                        'has_personal_belongings' => $personalQuoteRecord->has_personal_belongings,
                        'personal_belongings_aed' => $personalQuoteRecord->personal_belongings_aed,
                        'has_building' => $personalQuoteRecord->has_building,
                        'building_aed' => $personalQuoteRecord->building_aed,
                        'created_at' => $personalQuoteRecord->created_at,
                        'updated_at' => $personalQuoteRecord->updated_at,
                        'uuid' => $personalQuoteRecord->uuid,
                        'insurer_quote_no' => $personalQuoteRecord->insurer_quote_no,
                        'is_microchipped' => $personalQuoteRecord->is_microchipped,
                        'microchip_no' => $personalQuoteRecord->microchip_no,
                        'is_neutered' => $personalQuoteRecord->is_neutered,
                        'is_mixed_breed' => $personalQuoteRecord->is_mixed_breed,
                        'has_injury' => $personalQuoteRecord->has_injury,
                        'previous_quote_id' => $personalQuoteRecord->previous_quote_id,
                        'pa_id' => $personalQuoteRecord->pa_id,
                        'parent_duplicate_quote_id' => $personalQuoteRecord->parent_duplicate_quote_id,
                        'personal_quote_id' => $personalQuoteRecord->id,
                        'pet_type_id' => $personalQuoteRecord->pet_type_id,
                        'pet_age_id' => $personalQuoteRecord->pet_age_id,
                    ]);
                }
            });

        // Fetch data from pet_quote_details table and migrate into personal_quote_details table
        PersonalQuote::join('pet_quote_request', function ($petQuoteJoin){
            $petQuoteJoin->on('personal_quotes.uuid','=','pet_quote_request.uuid')
                ->andOn('personal_quotes.code','=','pet_quote_request.code');
            })
            ->join('pet_quote_request_detail', 'pet_quote_request.id', '=', 'pet_quote_request_detail.pet_quote_request_id')
            ->groupBy('personal_quotes.uuid')
            ->chunk(100, function ($petRequestDetailsRecord){
                foreach ($petRequestDetailsRecord as $petRequestDetailRecord){
                    PersonalQuoteDetail::firstOrCreate([
                        'personal_quote_id' => $petRequestDetailRecord->id,
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
        BikeQuote::join('bike_quote_request_detail', 'bike_quote_request.id', '=', 'bike_quote_request_detail.bike_quote_request_id')
            ->whereNull('bike_quote_request.personal_quote_id')
            ->groupBy('bike_quote_request.id')
            ->chunk(100, function ($bikeRecords){
                foreach ($bikeRecords as $bikeRecord){
                    BikeQuoteRequest::firstOrCreate([
                        'quote_type_id' => QuoteTypeId::Bike,
                        'uuid' => $bikeRecord->uuid,
                        'code' => $bikeRecord->code,
                        'first_name' => $bikeRecord->first_name,
                        'last_name' => $bikeRecord->last_name,
                        'dob' => $bikeRecord->dob,
                        'nationality_id' => $bikeRecord->nationality_id,
                        'email' => $bikeRecord->email,
                        'mobile_no' => $bikeRecord->mobile_no,
                        'source' => $bikeRecord->source,
                        'currently_insured_with' => $bikeRecord->currently_insured_with,
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
                        'updated_at' => $bikeRecord->updated_at
                    ]);
                }
            });

        // Fetch Old Bike Records from personal_quotes table and Migrate into bike_quote_request table
        PersonalQuote::join('bike_quote_request', function ($bikeQuoteJoin){
                $bikeQuoteJoin->on('personal_quotes.uuid','=','bike_quote_request.uuid')
                    ->andOn('personal_quotes.code','=','bike_quote_request.code');
            })
            ->whereNull('bike_quote_request.personal_quote_id')
            ->groupBy('personal_quotes.uuid')
            ->chunk(100, function ($personalQuoteRecords){
                foreach ($personalQuoteRecords as $personalQuoteRecord){
                    BikeQuoteRequest::firstOrCreate([
                        'bike_company_to_insure' => $personalQuoteRecord->bike_company_to_insure,
                        'bike_value' => $personalQuoteRecord->bike_value,
                        'year_of_manufacture' => $personalQuoteRecord->year_of_manufacture,
                        'uae_license_held_for_id' => $personalQuoteRecord->uae_license_held_for_id,
                        'gender' => $personalQuoteRecord->gender,
                        'lang' => $personalQuoteRecord->lang,
                        'customer_id' => $personalQuoteRecord->customer_id,
                        'is_synced' => $personalQuoteRecord->is_synced,
                        'additional_notes' => $personalQuoteRecord->additional_notes,
                        'reviver_name' => $personalQuoteRecord->reviver_name,
                        'promo_code' => $personalQuoteRecord->promo_code,
                        'code' => $personalQuoteRecord->code,
                        'uuid' => $personalQuoteRecord->uuid,
                        'previous_quote_id' => $personalQuoteRecord->previous_quote_id,
                        'pa_id' => $personalQuoteRecord->pa_id,
                        'other_email_addresses' => $personalQuoteRecord->pet_type_id,
                        'previous_advisor_id' => $personalQuoteRecord->pet_age_id,
                        'personal_quote_id' => $personalQuoteRecord->id,
                    ]);
                }
            });

        // Fetch data from pet_quote_details table and migrate into personal_quote_details table
        PersonalQuote::join('bike_quote_request', function ($bikeQuoteJoin){
                $bikeQuoteJoin->on('personal_quotes.uuid','=','bike_quote_request.uuid')
                    ->andOn('personal_quotes.code','=','bike_quote_request.code');
            })
            ->join('bike_quote_request_detail', 'bike_quote_request.id', '=', 'bike_quote_request_detail.bike_quote_request_id')
            ->groupBy('personal_quotes.uuid')
            ->chunk(100, function ($bikeRequestDetailsRecords){
                foreach ($bikeRequestDetailsRecords as $bikeRequestDetailsRecord){
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
                }

            });
    }
}
