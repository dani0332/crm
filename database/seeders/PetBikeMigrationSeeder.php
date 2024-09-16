<?php

namespace Database\Seeders;

use App\Enums\QuoteTypeId;
use App\Models\BikeQuote;
use App\Models\Customer;
use App\Models\InsuranceProvider;
use App\Models\Nationality;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use App\Models\PetQuote;
use App\Models\User;
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
        PetQuote::with('petQuoteRequestDetail')->whereNotNull('code')
            ->chunk(100, function ($petChunkRecords) {
                foreach ($petChunkRecords as $petChunkRecord) {
                    $customer = Customer::where('email', $petChunkRecord->email)->first();
                    $nationality = Nationality::where('id', $petChunkRecord->nationality_id)->first();

                    if (! PersonalQuote::where(['uuid' => trim($petChunkRecord->uuid), 'quote_type_id' => QuoteTypeId::Pet])->first()) {
                        $quote = PersonalQuote::updateOrCreate(
                            ['uuid' => trim($petChunkRecord->uuid), 'quote_type_id' => QuoteTypeId::Pet],
                            [
                                'code' => $petChunkRecord->code,
                                'first_name' => $petChunkRecord->first_name,
                                'last_name' => $petChunkRecord->last_name,
                                'dob' => $petChunkRecord->dob,
                                'nationality_id' => $nationality->id ?? null,
                                'email' => $petChunkRecord->email,
                                'mobile_no' => $petChunkRecord->mobile_no,
                                'source' => $petChunkRecord->source,
                                'customer_id' => $customer->id ?? null,
                                'device' => $petChunkRecord->device,
                                'reference_url' => $petChunkRecord->reference_url,
                                'policy_number' => $petChunkRecord->policy_number,
                                'advisor_id' => $petChunkRecord->advisor_id,
                                'premium' => $petChunkRecord->premium,
                                'renewal_batch' => $petChunkRecord->renewal_batch,
                                'policy_expiry_date' => $petChunkRecord->policy_expiry_date,
                                'previous_quote_policy_number' => $petChunkRecord->previous_quote_policy_number,
                                'renewal_import_code' => $petChunkRecord->renewal_import_code,
                                'previous_policy_expiry_date' => $petChunkRecord->previous_policy_expiry_date,
                                'previous_quote_policy_premium' => $petChunkRecord->previous_quote_policy_premium,
                                'policy_start_date' => $petChunkRecord->policy_start_date,
                                'policy_issuance_date' => $petChunkRecord->policy_issuance_date,
                                'payment_status_id' => $petChunkRecord->payment_status_id,
                                'quote_status_id' => $petChunkRecord->quote_status_id,
                                'notes' => $petChunkRecord->petQuoteRequestDetail?->notes ?? null,
                                'created_at' => $petChunkRecord->created_at,
                                'updated_at' => $petChunkRecord->updated_at,
                            ]
                        );

                        PetQuote::where('uuid', $petChunkRecord->uuid)->update(['personal_quote_id' => $quote->id]);

                        if (isset($petChunkRecord->petQuoteRequestDetail->id)) {
                            PersonalQuoteDetail::firstOrCreate(
                                ['personal_quote_id' => $quote->id],
                                [
                                    'pa_id' => $petChunkRecord->pa_id ?? null,
                                    'advisor_assigned_date' => $petChunkRecord->petQuoteRequestDetail?->advisor_assigned_date ?? null,
                                    'advisor_assigned_by_id' => $petChunkRecord->petQuoteRequestDetail?->advisor_assigned_by_id ?? null,
                                    'next_followup_date' => $petChunkRecord->petQuoteRequestDetail?->next_followup_date ?? null,
                                    'lost_reason_id' => $petChunkRecord->petQuoteRequestDetail?->lost_reason_id ?? null,
                                    'transapp_code' => $petChunkRecord->petQuoteRequestDetail?->transapp_code ?? null,
                                    'additional_notes' => $petChunkRecord->additional_notes ?? null,
                                    'utm_source' => $petChunkRecord->petQuoteRequestDetail?->utm_source ?? null,
                                    'utm_medium' => $petChunkRecord->petQuoteRequestDetail?->utm_medium ?? null,
                                    'utm_campaign' => $petChunkRecord->petQuoteRequestDetail?->utm_campaign ?? null,
                                ]
                            );
                        }
                    }
                }
            });

        // Bike Data Migration
        // Fetch Old Bike Records from bike_quote_request table and Dump into personal_quote table

        /* BikeQuote::with('bikeQuoteRequestDetail')->whereNotNull('code')
             ->chunk(100, function ($bikeChunkRecords) {
                 foreach ($bikeChunkRecords as $bikeChunkRecord) {
                     $insurer = InsuranceProvider::where('code', $bikeChunkRecord->currently_insured_with)->first();
                     $customer = Customer::where('email', $bikeChunkRecord->email)->first();
                     $nationality = Nationality::where('id', $bikeChunkRecord->nationality_id)->first();
                     $advisor = User::where('id', $bikeChunkRecord->advisor_id)->first();
                     $quote = PersonalQuote::firstOrCreate(['uuid' => trim($bikeChunkRecord->uuid), 'quote_type_id' => QuoteTypeId::Bike],
                         [
                             'quote_type_id' => QuoteTypeId::Bike,
                             'code' => $bikeChunkRecord->code,
                             'first_name' => $bikeChunkRecord->first_name,
                             'last_name' => $bikeChunkRecord->last_name,
                             'dob' => $bikeChunkRecord->dob,
                             'nationality_id' => $nationality->id ?? null,
                             'email' => $bikeChunkRecord->email,
                             'mobile_no' => $bikeChunkRecord->mobile_no,
                             'source' => $bikeChunkRecord->source,
                             'currently_insured_with_id' => $insurer->id ?? null,
                             'customer_id' => $customer->id ?? null,
                             'reference_url' => $bikeChunkRecord->reference_url,
                             'policy_number' => $bikeChunkRecord->policy_number,
                             'advisor_id' => $advisor->id ?? null,
                             'premium' => $bikeChunkRecord->premium,
                             'renewal_batch' => $bikeChunkRecord->renewal_batch,
                             'policy_expiry_date' => $bikeChunkRecord->policy_expiry_date,
                             'previous_quote_policy_number' => $bikeChunkRecord->previous_quote_policy_number,
                             'renewal_import_code' => $bikeChunkRecord->renewal_import_code,
                             'previous_policy_expiry_date' => $bikeChunkRecord->previous_policy_expiry_date,
                             'previous_quote_policy_premium' => $bikeChunkRecord->previous_quote_policy_premium,
                             'policy_start_date' => $bikeChunkRecord->policy_start_date,
                             'policy_issuance_date' => $bikeChunkRecord->policy_issuance_date,
                             'payment_status_id' => $bikeChunkRecord->payment_status_id,
                             'quote_status_id' => $bikeChunkRecord->quote_status_id,
                             'notes' => $bikeChunkRecord->bikeQuoteRequestDetail?->notes ?? null,
                             'created_at' => $bikeChunkRecord->created_at,
                             'updated_at' => $bikeChunkRecord->updated_at,
                         ]);

                     if ($bikeQuote = BikeQuote::where('uuid', $bikeChunkRecord->uuid)->first()) {
                         $bikeQuote->personal_quote_id = $quote->id;
                         $bikeQuote->save();

                         if (isset($bikeChunkRecord->bikeQuoteRequestDetail->id)) {
                             PersonalQuoteDetail::firstOrCreate(['personal_quote_id' => $quote->id], [
                                 'pa_id' => $bikeChunkRecord->pa_id ?? null,
                                 'advisor_assigned_date' => $bikeChunkRecord->bikeQuoteRequestDetail?->advisor_assigned_date ?? null,
                                 'advisor_assigned_by_id' => $bikeChunkRecord->bikeQuoteRequestDetail?->advisor_assigned_by_id ?? null,
                                 'next_followup_date' => $bikeChunkRecord->bikeQuoteRequestDetail?->next_followup_date ?? null,
                                 'lost_reason_id' => $bikeChunkRecord->bikeQuoteRequestDetail?->lost_reason_id ?? null,
                                 'transapp_code' => $bikeChunkRecord->bikeQuoteRequestDetail?->transapp_code ?? null,
                                 'additional_notes' => $bikeChunkRecord->additional_notes ?? null,
                                 'utm_source' => $bikeChunkRecord->bikeQuoteRequestDetail?->utm_source ?? null,
                                 'utm_medium' => $bikeChunkRecord->bikeQuoteRequestDetail?->utm_medium ?? null,
                                 'utm_campaign' => $bikeChunkRecord->bikeQuoteRequestDetail?->utm_campaign ?? null,
                             ]);
                         }
                     }
                 }
             });*/
    }
}
