<?php

namespace Tests\Support\Schema;

use Illuminate\Database\Schema\Blueprint;

class RenewalsSchema
{
    public function register(): void
    {
        $this->ensureTables();
    }

    private function ensureTables(): void
    {
        SchemaUtils::ensureTables([
            'bike_quote_request' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('personal_quote_id')->nullable();
                $table->string('uuid')->nullable();
                $table->string('code')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->nullable();
                $table->string('mobile_no')->nullable();
                $table->string('gender')->nullable();
                $table->date('dob')->nullable();
                $table->string('lang')->nullable();
                $table->string('source')->nullable();
                $table->unsignedBigInteger('quote_status_id')->nullable();
                $table->unsignedBigInteger('advisor_id')->nullable();
                $table->string('assignment_type')->nullable();
                $table->unsignedBigInteger('renewal_batch_id')->nullable();
                $table->string('previous_quote_policy_number')->nullable();
                $table->decimal('previous_quote_policy_premium', 15, 2)->nullable();
                $table->decimal('previous_quote_policy_commission', 15, 2)->nullable();
                $table->unsignedBigInteger('previous_advisor_id')->nullable();
                $table->date('previous_policy_start_date')->nullable();
                $table->date('previous_policy_expiry_date')->nullable();
                $table->timestamp('transaction_approved_at')->nullable();
                $table->string('currently_insured_with')->nullable();
                $table->unsignedBigInteger('previous_quote_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('nationality_id')->nullable();
                $table->string('bike_company_to_insure')->nullable();
                $table->string('year_of_manufacture')->nullable();
                $table->unsignedBigInteger('make_id')->nullable();
                $table->unsignedBigInteger('model_id')->nullable();
                $table->unsignedBigInteger('model_detail_id')->nullable();
                $table->string('cubic_capacity')->nullable();
                $table->unsignedBigInteger('emirate_of_registration_id')->nullable();
                $table->decimal('bike_value_tier', 15, 2)->nullable();
                $table->string('chassis_number')->nullable();
                $table->unsignedBigInteger('vehicle_type_id')->nullable();
                $table->integer('seat_capacity')->nullable();
                $table->string('year_of_first_registration')->nullable();
                $table->unsignedBigInteger('bike_type_insurance_id')->default(1);
                $table->unsignedBigInteger('uae_license_held_for_id')->nullable();
                $table->unsignedBigInteger('back_home_license_held_for_id')->nullable();
                $table->decimal('bike_value', 15, 2)->nullable();
                $table->unsignedBigInteger('claim_history_id')->nullable();
                $table->boolean('has_ncd_supporting_documents')->nullable();
                $table->unsignedBigInteger('insurance_type_id')->nullable();
                $table->string('current_insurance_status')->nullable();
                $table->decimal('premium', 15, 2)->nullable();
                $table->string('policy_number')->nullable();
                $table->unsignedBigInteger('insurance_provider_id')->nullable();
                $table->timestamps();
            },
            'bike_quote_request_detail' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bike_quote_request_id')->nullable();
                $table->timestamps();
            },
            'renewals_upload_leads' => function (Blueprint $table) {
                $table->id();
                $table->string('file_name');
                $table->string('file_path')->nullable();
                $table->string('quote_type')->nullable();
                $table->string('status')->nullable();
                $table->string('renewal_import_code')->nullable();
                $table->string('renewal_import_type')->nullable();
                $table->unsignedInteger('good')->default(0);
                $table->unsignedInteger('cannot_upload')->default(0);
                $table->unsignedInteger('total_records')->nullable();
                $table->boolean('is_sic')->default(0);
                $table->integer('total_records')->default(0);
                $table->integer('good')->default(0);
                $table->integer('cannot_upload')->default(0);
                $table->integer('skip_plans')->nullable();
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->timestamps();
            },
            'renewal_quote_processes' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('renewals_upload_lead_id');
                $table->unsignedBigInteger('quote_id')->nullable();
                $table->json('data');
                $table->longText('validation_errors')->nullable();
                $table->longText('step_errors')->nullable();
                $table->string('status');
                $table->string('quote_type');
                $table->string('type');
                $table->string('fetch_plans_status')->nullable();
                $table->boolean('email_sent')->default(false);
                $table->string('batch')->nullable();
                $table->string('policy_number')->nullable();
                $table->string('renewal_batch_id')->nullable();
                $table->unsignedBigInteger('insurance_provider_transition_id')->nullable();
                $table->string('step')->nullable();
                $table->string('retry_count')->nullable();
                $table->string('last_step_attempted')->nullable();
                $table->softDeletes();
                $table->timestamps();
            },
            'renewal_insurance_provider_transitions' => function (Blueprint $table) {
                $table->id();
                $table->integer('source_insurance_provider_id');
                $table->integer('target_insurance_provider_id');
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(1);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            },
            'renewal_batch_segment_user' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('renewal_batch_id');
                $table->unsignedBigInteger('advisor_id');
                $table->string('segment_type');
                $table->timestamps();
            },
            'renewal_batch_slab' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('renewal_batch_id');
                $table->unsignedBigInteger('team_id')->nullable();
                $table->integer('min')->nullable();
                $table->integer('max')->nullable();
                $table->timestamps();
            },
            'renewal_batch_deadline' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('renewal_batch_id');
                $table->date('deadline_date');
                $table->timestamps();
            },
            'car_lost_quote_logs' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('car_quote_request_id')->nullable();
                $table->unsignedInteger('quote_status_id')->nullable();
                $table->string('status')->nullable();
                $table->text('reason')->nullable();
                $table->timestamps();
            },
            'customer_addresses' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('type')->nullable();
                $table->unsignedBigInteger('quote_type_id')->nullable();
                $table->string('quote_uuid')->nullable();
                $table->string('office_number')->nullable();
                $table->string('floor_number')->nullable();
                $table->string('building_name')->nullable();
                $table->string('street')->nullable();
                $table->string('area')->nullable();
                $table->string('city')->nullable();
                $table->string('landmark')->nullable();
                $table->boolean('is_default')->default(0);
                $table->boolean('is_courier_address')->default(0);
                $table->timestamps();
            },
            'quote_request_entity_mapping' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('quote_type_id')->nullable();
                $table->unsignedBigInteger('quote_request_id')->nullable();
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->string('entity_type_code')->nullable();
                $table->timestamps();
            },
        ]);

        $this->ensureColumns();
    }

    private function ensureColumns(): void
    {
        $commonRenewalColumns = [
            'quote_status_date' => function (Blueprint $table) {
                $table->dateTime('quote_status_date')->nullable();
            },
            'renewal_batch' => function (Blueprint $table) {
                $table->string('renewal_batch')->nullable();
            },
            'source' => function (Blueprint $table) {
                $table->string('source')->nullable();
            },
            'insurance_provider_id' => function (Blueprint $table) {
                $table->unsignedBigInteger('insurance_provider_id')->nullable();
            },
            'advisor_id' => function (Blueprint $table) {
                $table->unsignedBigInteger('advisor_id')->nullable();
            },
        ];

        $lobRenewalColumns = [
            'previous_quote_policy_number' => fn (Blueprint $t) => $t->string('previous_quote_policy_number')->nullable(),
            'previous_quote_policy_premium' => fn (Blueprint $t) => $t->decimal('previous_quote_policy_premium', 15, 2)->nullable(),
            'previous_quote_policy_commission' => fn (Blueprint $t) => $t->decimal('previous_quote_policy_commission', 15, 2)->nullable(),
            'previous_advisor_id' => fn (Blueprint $t) => $t->unsignedBigInteger('previous_advisor_id')->nullable(),
            'previous_policy_start_date' => fn (Blueprint $t) => $t->date('previous_policy_start_date')->nullable(),
            'previous_policy_expiry_date' => fn (Blueprint $t) => $t->date('previous_policy_expiry_date')->nullable(),
            'transaction_approved_at' => fn (Blueprint $t) => $t->timestamp('transaction_approved_at')->nullable(),
            'previous_quote_id' => fn (Blueprint $t) => $t->unsignedBigInteger('previous_quote_id')->nullable(),
            'customer_id' => fn (Blueprint $t) => $t->unsignedBigInteger('customer_id')->nullable(),
            'nationality_id' => fn (Blueprint $t) => $t->unsignedBigInteger('nationality_id')->nullable(),
            'gender' => fn (Blueprint $t) => $t->string('gender')->nullable(),
            'dob' => fn (Blueprint $t) => $t->date('dob')->nullable(),
            'lang' => fn (Blueprint $t) => $t->string('lang')->nullable(),
        ];

        SchemaUtils::ensureColumns([
            'renewals_upload_leads' => [
                'is_deleted' => function (Blueprint $table) {
                    $table->boolean('is_deleted')->default(0);
                },
                'created_by_id' => function (Blueprint $table) {
                    $table->unsignedBigInteger('created_by_id')->nullable();
                },
            ],
            'renewal_quote_processes' => [
                'email_sent' => function (Blueprint $table) {
                    $table->boolean('email_sent')->default(false);
                },
            ],
            'health_quote_request' => array_merge([
                'health_quote_id' => function (Blueprint $table) {
                    $table->unsignedBigInteger('health_quote_id')->nullable();
                },
            ], $commonRenewalColumns),
            'car_quote_request' => array_merge([
                'car_quote_id' => function (Blueprint $table) {
                    $table->unsignedBigInteger('car_quote_id')->nullable();
                },
                'currently_insured_with' => function (Blueprint $table) {
                    $table->string('currently_insured_with')->nullable();
                },
                'uae_license_held_for_id' => fn (Blueprint $t) => $t->unsignedBigInteger('uae_license_held_for_id')->nullable(),
                'back_home_license_held_for_id' => fn (Blueprint $t) => $t->unsignedBigInteger('back_home_license_held_for_id')->nullable(),
                'car_type_insurance_id' => fn (Blueprint $t) => $t->unsignedBigInteger('car_type_insurance_id')->nullable(),
            ], $commonRenewalColumns),
            'cycle_quote_request' => [
                'uuid' => fn (Blueprint $t) => $t->string('uuid')->nullable(),
                'code' => fn (Blueprint $t) => $t->string('code')->nullable(),
                'transaction_approved_at' => fn (Blueprint $t) => $t->timestamp('transaction_approved_at')->nullable(),
            ],
            'pet_quote_request' => array_merge($lobRenewalColumns, [
                'no_of_pets_to_insure' => fn (Blueprint $t) => $t->integer('no_of_pets_to_insure')->nullable(),
                'type_of_pet1' => fn (Blueprint $t) => $t->string('type_of_pet1')->nullable(),
                'breed_of_pet1' => fn (Blueprint $t) => $t->string('breed_of_pet1')->nullable(),
                'ilivein_accommodation_type_id' => fn (Blueprint $t) => $t->unsignedBigInteger('ilivein_accommodation_type_id')->nullable(),
                'iam_possesion_type_id' => fn (Blueprint $t) => $t->unsignedBigInteger('iam_possesion_type_id')->nullable(),
                'is_microchipped' => fn (Blueprint $t) => $t->boolean('is_microchipped')->nullable(),
                'microchip_no' => fn (Blueprint $t) => $t->string('microchip_no')->nullable(),
                'is_neutered' => fn (Blueprint $t) => $t->boolean('is_neutered')->nullable(),
                'is_mixed_breed' => fn (Blueprint $t) => $t->boolean('is_mixed_breed')->nullable(),
                'has_injury' => fn (Blueprint $t) => $t->boolean('has_injury')->nullable(),
                'pet_type_id' => fn (Blueprint $t) => $t->unsignedBigInteger('pet_type_id')->nullable(),
                'accomodation_type_id' => fn (Blueprint $t) => $t->unsignedBigInteger('accomodation_type_id')->nullable(),
                'pet_age_id' => fn (Blueprint $t) => $t->unsignedBigInteger('pet_age_id')->nullable(),
            ]),
            'home_quote_request' => array_merge($lobRenewalColumns, [
                'transaction_type_id' => fn (Blueprint $t) => $t->unsignedBigInteger('transaction_type_id')->nullable(),
                'has_claimed_losses' => fn (Blueprint $t) => $t->boolean('has_claimed_losses')->nullable(),
                'ilivein_accommodation_type_id' => fn (Blueprint $t) => $t->unsignedBigInteger('ilivein_accommodation_type_id')->nullable(),
                'accommodation_type_id' => fn (Blueprint $t) => $t->unsignedBigInteger('accommodation_type_id')->nullable(),
                'iam_possesion_type_id' => fn (Blueprint $t) => $t->unsignedBigInteger('iam_possesion_type_id')->nullable(),
                'owner_occupancy_type_id' => fn (Blueprint $t) => $t->unsignedBigInteger('owner_occupancy_type_id')->nullable(),
                'coverage_type_id' => fn (Blueprint $t) => $t->unsignedBigInteger('coverage_type_id')->nullable(),
                'building_value' => fn (Blueprint $t) => $t->decimal('building_value', 15, 2)->nullable(),
                'contents_value_id' => fn (Blueprint $t) => $t->unsignedBigInteger('contents_value_id')->nullable(),
                'personal_belongings_value_id' => fn (Blueprint $t) => $t->unsignedBigInteger('personal_belongings_value_id')->nullable(),
                'sub_area_id' => fn (Blueprint $t) => $t->unsignedBigInteger('sub_area_id')->nullable(),
                'is_property_rented_holiday_home' => fn (Blueprint $t) => $t->boolean('is_property_rented_holiday_home')->nullable(),
                'previous_building_aed' => fn (Blueprint $t) => $t->decimal('previous_building_aed', 15, 2)->nullable(),
                'previous_contents_aed' => fn (Blueprint $t) => $t->decimal('previous_contents_aed', 15, 2)->nullable(),
                'previous_personal_belongings_aed' => fn (Blueprint $t) => $t->decimal('previous_personal_belongings_aed', 15, 2)->nullable(),
                'has_contents' => fn (Blueprint $t) => $t->boolean('has_contents')->nullable(),
                'has_personal_belongings' => fn (Blueprint $t) => $t->boolean('has_personal_belongings')->nullable(),
                'has_building' => fn (Blueprint $t) => $t->boolean('has_building')->nullable(),
                'contents_aed' => fn (Blueprint $t) => $t->decimal('contents_aed', 15, 2)->nullable(),
                'personal_belongings_aed' => fn (Blueprint $t) => $t->decimal('personal_belongings_aed', 15, 2)->nullable(),
                'building_aed' => fn (Blueprint $t) => $t->decimal('building_aed', 15, 2)->nullable(),
                'address' => fn (Blueprint $t) => $t->string('address')->nullable(),
                'possession_type_id' => fn (Blueprint $t) => $t->unsignedBigInteger('possession_type_id')->nullable(),
                'company_name' => fn (Blueprint $t) => $t->string('company_name')->nullable(),
                'company_address' => fn (Blueprint $t) => $t->string('company_address')->nullable(),
            ]),
            'yacht_quote_request' => array_merge($lobRenewalColumns, [
                'boat_details' => fn (Blueprint $t) => $t->text('boat_details')->nullable(),
                'engine_details' => fn (Blueprint $t) => $t->text('engine_details')->nullable(),
                'claim_experience' => fn (Blueprint $t) => $t->string('claim_experience')->nullable(),
                'sum_insured_value' => fn (Blueprint $t) => $t->decimal('sum_insured_value', 15, 2)->nullable(),
                'use' => fn (Blueprint $t) => $t->string('use')->nullable(),
                'operator_experience' => fn (Blueprint $t) => $t->integer('operator_experience')->nullable(),
            ]),
            'business_quote_request' => array_merge($lobRenewalColumns, [
                'transaction_type_id' => fn (Blueprint $t) => $t->unsignedBigInteger('transaction_type_id')->nullable(),
                'business_cover_type_id' => fn (Blueprint $t) => $t->unsignedBigInteger('business_cover_type_id')->nullable(),
                'communication_mode_id' => fn (Blueprint $t) => $t->unsignedBigInteger('communication_mode_id')->nullable(),
                'time_to_contact' => fn (Blueprint $t) => $t->string('time_to_contact')->nullable(),
                'boat_details' => fn (Blueprint $t) => $t->text('boat_details')->nullable(),
                'engine_details' => fn (Blueprint $t) => $t->text('engine_details')->nullable(),
                'claims_experience' => fn (Blueprint $t) => $t->string('claims_experience')->nullable(),
                'sum_insured_value' => fn (Blueprint $t) => $t->decimal('sum_insured_value', 15, 2)->nullable(),
                'use' => fn (Blueprint $t) => $t->string('use')->nullable(),
                'operators_experience' => fn (Blueprint $t) => $t->string('operators_experience')->nullable(),
                'interest' => fn (Blueprint $t) => $t->string('interest')->nullable(),
                'contact_person_designation' => fn (Blueprint $t) => $t->string('contact_person_designation')->nullable(),
                'company_address' => fn (Blueprint $t) => $t->string('company_address')->nullable(),
                'turnover_aed' => fn (Blueprint $t) => $t->decimal('turnover_aed', 15, 2)->nullable(),
                'health_plan_type_id' => fn (Blueprint $t) => $t->unsignedBigInteger('health_plan_type_id')->nullable(),
                'number_of_categories' => fn (Blueprint $t) => $t->integer('number_of_categories')->nullable(),
                'has_existing_group_policy' => fn (Blueprint $t) => $t->boolean('has_existing_group_policy')->nullable(),
                'group_medical_type_id' => fn (Blueprint $t) => $t->unsignedBigInteger('group_medical_type_id')->nullable(),
                'company_activity_type_id' => fn (Blueprint $t) => $t->unsignedBigInteger('company_activity_type_id')->nullable(),
                'emirates_id' => fn (Blueprint $t) => $t->string('emirates_id')->nullable(),
            ]),
            'personal_quotes' => [
                'asset_value' => fn (Blueprint $t) => $t->decimal('asset_value', 15, 2)->nullable(),
                'company_name' => fn (Blueprint $t) => $t->string('company_name')->nullable(),
                'company_address' => fn (Blueprint $t) => $t->string('company_address')->nullable(),
                'previous_quote_id' => fn (Blueprint $t) => $t->unsignedBigInteger('previous_quote_id')->nullable(),
                'quote_id' => fn (Blueprint $t) => $t->unsignedBigInteger('quote_id')->nullable(),
            ],
            'car_quote_request_detail' => [
                'chassis_number' => fn (Blueprint $t) => $t->string('chassis_number')->nullable(),
            ],
        ]);
    }
}
