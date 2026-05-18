<?php

namespace Tests\Support\Schema;

use Illuminate\Database\Schema\Blueprint;

class CoreSchema
{
    public function register(): void
    {
        $this->ensureAuditTables();
        $this->ensureActivityLogTables();
        $this->ensureUserAndPermissionTables();
        $this->ensureReferenceTables();
        $this->ensureApplicationStorageTable();
        $this->ensurePcpConfigTable();
        $this->ensureTeamTables();
        $this->ensureUserManagementTables();
        $this->ensureQueueTables();
        $this->ensureLookupTables();
        $this->ensureCustomerTables();
        $this->ensureInsuranceProviderTables();
        $this->ensureQuoteTables();
        $this->ensureCatARevivalBuyLeadSupport();
        $this->ensureBusinessQuoteTables();
        $this->ensureEmbeddedProductTables();
        $this->ensurePaymentTables();
        $this->ensureDocumentTables();
        $this->ensurePolicyIssuanceTables();
        $this->ensureSendUpdateTables();
        $this->ensureSageTables();
        $this->ensureCustomerAdditionalContactTables();
        $this->ensureEPLogsTables();
        $this->ensureGroupMedicalFormDropdownTables();
    }

    private function ensureAuditTables(): void
    {
        SchemaUtils::ensureTables([
            'audits' => function (Blueprint $table) {
                $table->id();
                $table->string('user_type')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('event');
                $table->morphs('auditable');
                $table->text('old_values')->nullable();
                $table->text('new_values')->nullable();
                $table->text('url')->nullable();
                $table->ipAddress('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->string('tags')->nullable();
                $table->timestamps();
            },
        ]);
    }

    private function ensureActivityLogTables(): void
    {
        SchemaUtils::ensureTables([
            'activity_log' => function (Blueprint $table) {
                $table->id();
                $table->string('log_name')->nullable();
                $table->text('description')->nullable();
                $table->string('url')->nullable();
                $table->string('feature')->nullable();
                $table->ipAddress('ip_address')->nullable();
                $table->string('subject_type')->nullable();
                $table->string('event')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->string('causer_type')->nullable();
                $table->unsignedBigInteger('causer_id')->nullable();
                $table->json('attribute_changes')->nullable();
                $table->text('properties')->nullable();
                $table->string('batch_uuid')->nullable();
                $table->string('code')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();
            },
        ]);
    }

    private function ensureUserAndPermissionTables(): void
    {
        SchemaUtils::ensureTables([
            'users' => function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('remember_token')->nullable();
                $table->tinyInteger('is_active')->default(1);
                $table->integer('status')->nullable();
                $table->timestamp('logout_at')->nullable();
                // User-management columns used in UserController@update and related flows.
                $table->string('mobile_no')->nullable();
                $table->string('landline_no')->nullable();
                $table->string('calendar_link')->nullable();
                $table->string('phone_calendar_link')->nullable();
                $table->unsignedBigInteger('department_id')->nullable();
                $table->unsignedBigInteger('rm_category_id')->nullable();
                $table->text('additional_team_ids')->nullable();
                $table->unsignedBigInteger('sub_team_id')->nullable();
                // Used by CheckLastLoginMiddleware (last_login_check)
                $table->timestamp('last_login')->nullable();
                $table->timestamps();
            },
            'roles' => function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            },
            'model_has_roles' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->timestamps();
            },
            'permissions' => function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            },
            'model_has_permissions' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->timestamps();
            },
        ]);

        SchemaUtils::ensureTable('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });
    }

    private function ensureReferenceTables(): void
    {
        SchemaUtils::ensureTables([
            'quote_type' => function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('short_code')->nullable();
                $table->string('text')->nullable();
                $table->boolean('is_active')->default(1);
                $table->integer('sort_order')->nullable();
                $table->timestamps();
            },
            'quote_status' => function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text')->nullable();
                $table->string('text_ar')->nullable();
                $table->boolean('is_active')->default(1);
                $table->integer('sort_order')->nullable();
                $table->boolean('is_deleted')->default(0);
                $table->string('uuid')->nullable();
                $table->string('created_by')->nullable();
                $table->string('updated_by')->nullable();
                $table->softDeletes();
                $table->timestamps();
            },
            'quote_status_map' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('quote_status_id');
                $table->unsignedBigInteger('quote_type_id');
                $table->integer('sort_order')->nullable();
                $table->timestamps();
            },
            'allocation_configurations' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('quote_type_id');
                $table->string('quote_type');
                $table->json('config')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            },
            'car_make' => function (Blueprint $table) {
                $table->id();
                $table->string('text');
                $table->string('code')->nullable();
                $table->timestamps();
                $table->softDeletes();
            },
            'car_model' => function (Blueprint $table) {
                $table->id();
                $table->string('text');
                $table->string('code')->nullable();
                $table->string('car_make_code')->nullable();
                $table->unsignedBigInteger('vehicle_type_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            },
            'nationality' => function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->integer('sort_order')->nullable();
                $table->softDeletes();
                $table->timestamps();
            },
            'currency_type' => function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
            'marital_status' => function (Blueprint $table) {
                $table->id();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
            'life_insurance_purpose' => function (Blueprint $table) {
                $table->id();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
            'life_number_of_year' => function (Blueprint $table) {
                $table->id();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
            'life_insurance_tenure' => function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text')->nullable();
                $table->string('text_ar')->nullable();
                $table->boolean('is_active')->default(1);
                $table->boolean('is_deleted')->default(0);
                $table->integer('sort_order')->nullable();
                $table->timestamps();
            },
            'emirates' => function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
                $table->softDeletes();
            },
            'claim_history' => function (Blueprint $table) {
                $table->id();
                $table->string('text');
                $table->timestamps();
                $table->softDeletes();
            },
            'uae_license_held_for' => function (Blueprint $table) {
                $table->id();
                $table->string('text');
                $table->timestamps();
                $table->softDeletes();
            },
            'health_cover_for' => function (Blueprint $table) {
                $table->id();
                $table->string('text')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
            'health_lead_type' => function (Blueprint $table) {
                $table->id();
                $table->string('text')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
            'salary_band' => function (Blueprint $table) {
                $table->id();
                $table->string('text')->nullable();
                $table->boolean('is_active')->default(1);
                $table->integer('sort_order')->nullable();
                $table->timestamps();
            },
            'member_category' => function (Blueprint $table) {
                $table->id();
                $table->string('text')->nullable();
                $table->boolean('is_active')->default(1);
                $table->integer('sort_order')->nullable();
                $table->timestamps();
            },
            'tiers' => function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->decimal('min_price', 15, 2)->nullable();
                $table->decimal('max_price', 15, 2)->nullable();
                $table->decimal('cost_per_lead', 15, 2)->nullable();
                $table->boolean('can_handle_null_value')->default(0);
                $table->boolean('can_handle_ecommerce')->default(0);
                $table->boolean('is_active')->default(1);
                $table->boolean('can_handle_tpl')->default(0);
                $table->boolean('is_tpl_renewals')->default(0);
                $table->timestamps();
            },
        ]);
    }

    private function ensureQuoteTables(): void
    {
        SchemaUtils::ensureTables([
            'personal_quotes' => function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->string('code')->nullable();
                $table->unsignedBigInteger('quote_type_id');
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('insurer_quote_number')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->nullable();
                $table->string('mobile_no')->nullable();
                $table->date('dob')->nullable();
                $table->string('source')->nullable();
                $table->string('device')->nullable();
                $table->unsignedBigInteger('advisor_id')->nullable();
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->unsignedBigInteger('updated_by_id')->nullable();
                $table->unsignedBigInteger('quote_status_id')->nullable();
                $table->unsignedBigInteger('payment_status_id')->nullable();
                $table->string('insurer_aml_status')->nullable();
                $table->timestamps();
            },
            'personal_quote_details' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('personal_quote_id');
                $table->unsignedBigInteger('previous_advisor_id')->nullable();
                $table->unsignedBigInteger('pa_id')->nullable();
                $table->string('reviver_name', 100)->nullable();
                $table->dateTime('advisor_assigned_date')->nullable();
                $table->unsignedBigInteger('advisor_assigned_by_id')->nullable();
                $table->dateTime('next_followup_date')->nullable();
                $table->unsignedBigInteger('lost_reason_id')->nullable();
                $table->string('transapp_code')->nullable();
                $table->string('additional_notes', 500)->nullable();
                $table->string('utm_source', 256)->nullable();
                $table->string('utm_medium', 256)->nullable();
                $table->string('utm_campaign', 256)->nullable();
                $table->string('insly_id', 50)->nullable();
                $table->string('risk_score_override', 255)->nullable();
                $table->string('risk_score_override_by', 255)->nullable();
                $table->dateTime('risk_score_override_date')->nullable();
                $table->string('insly_advisor_name', 50)->nullable();
                $table->dateTime('chat_initiated_at')->nullable();
                $table->dateTime('temp_advisor_assigned_date')->nullable();
                $table->string('insurer_quote_email', 100)->nullable();
                $table->boolean('is_deleted')->default(0);
                $table->string('membership_code', 50)->nullable();
                $table->boolean('has_duplicate_lead')->default(0);
                $table->string('existing_record_uuid', 50)->nullable();
                $table->string('utm_id', 256)->nullable();
                $table->string('utm_term', 256)->nullable();
                $table->string('utm_content', 256)->nullable();
                $table->string('building_and_flat_number', 255)->nullable();
                $table->timestamps();
                $table->unique('personal_quote_id');
            },
            'quote_journey' => function (Blueprint $table) {
                $table->id();
                $table->string('quote_uuid');
                $table->unsignedBigInteger('quote_type_id');
                $table->string('text');
                $table->string('status');
                $table->timestamps();
            },
            'life_quote_request' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('personal_quote_id');
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->nullable();
                $table->string('mobile_no')->nullable();
                $table->decimal('height', 8, 2)->nullable();
                $table->decimal('weight', 8, 2)->nullable();
                $table->decimal('bmi', 8, 2)->nullable();
                $table->integer('age')->nullable();
                $table->decimal('sum_insured_value', 15, 2)->nullable();
                $table->unsignedBigInteger('sum_insured_currency_id')->nullable();
                $table->unsignedBigInteger('nationality_id')->nullable();
                $table->unsignedBigInteger('marital_status_id')->nullable();
                $table->unsignedBigInteger('purpose_of_insurance_id')->nullable();
                $table->unsignedBigInteger('number_of_years_id')->nullable();
                $table->boolean('is_smoker')->default(0);
                $table->string('gender')->nullable();
                $table->text('others_info')->nullable();
                $table->string('uuid')->nullable();
                $table->string('lang')->nullable();
                $table->timestamps();
            },
            'car_quote_request' => function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->string('code')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->nullable();
                $table->string('mobile_no')->nullable();
                $table->date('dob')->nullable();
                $table->string('source')->nullable();
                $table->string('device')->nullable();
                $table->decimal('premium', 10, 2)->nullable();
                $table->unsignedBigInteger('insurance_provider_id')->nullable();
                $table->unsignedBigInteger('plan_id')->nullable();
                $table->unsignedBigInteger('quote_status_id')->nullable();
                $table->timestamp('quote_status_date')->nullable();
                $table->unsignedBigInteger('payment_status_id')->nullable();
                $table->timestamp('payment_paid_at')->nullable(); // Required for payment approval updates
                $table->unsignedBigInteger('advisor_id')->nullable();
                $table->timestamp('aig_flow_executed_at')->nullable();
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->string('previous_quote_policy_number')->nullable();
                $table->date('previous_policy_expiry_date')->nullable();
                $table->date('policy_expiry_date')->nullable();
                $table->date('policy_booking_date')->nullable();
                $table->boolean('is_ecommerce')->default(0);
                $table->decimal('car_value', 15, 2)->nullable();
                $table->unsignedBigInteger('car_make_id')->nullable();
                $table->unsignedBigInteger('car_model_id')->nullable();
                $table->unsignedBigInteger('updated_by_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable(); // Required for UpdateCustomerEmail listener
                $table->unsignedBigInteger('nationality_id')->nullable();
                $table->unsignedBigInteger('emirate_of_registration_id')->nullable();
                $table->string('insurer_quote_number')->nullable();
                $table->string('registration_type')->nullable();
                $table->string('vehicle_use')->nullable();
                $table->boolean('is_modified')->default(false);
                $table->string('driver_name')->nullable();
                $table->integer('insurer_api_status_id')->nullable();
                $table->integer('api_issuance_status_id')->nullable();
                $table->boolean('policy_issuance_automation_enabled')->default(false);
                $table->timestamps();
                $table->softDeletes(); // BaseModel uses SoftDeletes trait
            },
            'quotes_flow_details' => function (Blueprint $table) {
                $table->id();
                $table->string('quote_uuid');
                $table->unsignedBigInteger('quote_type_id');
                $table->integer('flow_type')->nullable();
                $table->string('flow_id');
                $table->timestamp('started_at')->nullable();
                $table->timestamps();
            },
            'device_quote' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('personal_quote_id');
                $table->string('imei')->nullable();
                $table->string('device_make')->nullable();
                $table->string('device_model')->nullable();
                $table->string('device_type')->nullable();
                $table->decimal('device_value', 15, 2)->nullable();
                $table->string('device_condition')->nullable();
                $table->string('purchase_date')->nullable();
                $table->timestamps();
            },
            'device_quote_request' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('personal_quote_id');
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->nullable();
                $table->string('mobile_no')->nullable();
                $table->string('month_of_purchase')->nullable();
                $table->string('year_of_purchase')->nullable();
                $table->unsignedBigInteger('make_id')->nullable();
                $table->unsignedBigInteger('model_id')->nullable();
                $table->string('imei')->nullable();
                $table->string('purchase_date')->nullable();
                $table->timestamps();
            },
            'quote_sync' => function (Blueprint $table) {
                $table->id();
                $table->string('quote_uuid')->nullable();
                $table->unsignedBigInteger('quote_type_id')->nullable();
                $table->text('updated_fields')->nullable();
                $table->boolean('is_synced')->default(0);
                $table->timestamp('synced_at')->nullable();
                $table->string('status')->nullable();
                $table->text('error')->nullable();
                $table->timestamps();
            },
            'car_quote_request_detail' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('car_quote_request_id')->nullable();
                $table->string('engagement_level')->nullable();
                $table->dateTime('engagement_level_updated_at')->nullable();
                $table->timestamps();
            },
            'car_plan' => function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text')->nullable();
                $table->string('plan_name')->nullable();
                $table->string('repair_type')->nullable();
                $table->boolean('is_active')->default(1);
                $table->unsignedBigInteger('provider_id')->nullable();
                $table->timestamps();
                $table->softDeletes(); // BaseModel uses SoftDeletes trait
            },
            'car_plan_coverage' => function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text')->nullable();
                $table->string('text_ar')->nullable();
                $table->string('value')->nullable();
                $table->string('value_ar')->nullable();
                $table->string('type')->nullable();
                $table->unsignedBigInteger('plan_id')->nullable();
                $table->timestamps();
            },
            'quote_batches' => function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->timestamps();
            },
            'renewal_batches' => function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->integer('month')->nullable();
                $table->integer('year')->nullable();
                $table->unsignedBigInteger('quote_type_id')->nullable();
                $table->timestamps();
            },
            'travel_quote_request' => function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->nullable();
                $table->string('code')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('direction_code')->nullable();
                $table->string('coverage_code')->nullable();
                $table->string('policy_number')->nullable();
                $table->string('source')->nullable();
                $table->unsignedBigInteger('sub_source_id')->nullable();
                $table->unsignedBigInteger('quote_status_id')->nullable();
                $table->timestamp('quote_status_date')->nullable();
                $table->unsignedBigInteger('advisor_id')->nullable();
                $table->unsignedBigInteger('previous_advisor_id')->nullable();
                $table->unsignedBigInteger('previous_quote_id')->nullable();
                $table->date('policy_expiry_date')->nullable();
                $table->string('renewal_batch')->nullable();
                $table->string('renewal_import_code')->nullable();
                $table->string('previous_quote_policy_number')->nullable();
                $table->decimal('previous_quote_policy_premium', 10, 2)->nullable();
                $table->string('device')->nullable();
                $table->unsignedBigInteger('plan_id')->nullable();
                $table->date('policy_start_date')->nullable();
                $table->date('policy_issuance_date')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('parent_duplicate_quote_id')->nullable();
                $table->boolean('is_ecommerce')->default(0);
                $table->date('policy_booking_date')->nullable();
                $table->string('insurer_quote_number')->nullable();
                $table->unsignedBigInteger('policy_issuance_status_id')->nullable();
                $table->string('policy_issuance_status_other')->nullable();
                $table->string('sic_advisor_requested')->nullable();
                $table->string('aml_status')->nullable();
                $table->unsignedBigInteger('insurance_provider_id')->nullable();
                $table->string('insurer_aml_status')->nullable();
                $table->unsignedBigInteger('renewal_batch_id')->nullable();
                $table->date('previous_policy_expiry_date')->nullable();
                $table->date('dob')->nullable();
                $table->unsignedBigInteger('nationality_id')->nullable();
                $table->timestamp('transaction_approved_at')->nullable();
                $table->string('assignment_type')->nullable();
                $table->string('gender')->nullable();
                $table->decimal('premium', 10, 2)->nullable();
                $table->unsignedBigInteger('payment_status_id')->nullable();
                $table->unsignedBigInteger('currently_located_in_id')->nullable();
                $table->unsignedBigInteger('api_issuance_status_id')->nullable();
                $table->unsignedBigInteger('insurer_api_status_id')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->integer('days_cover_for')->nullable();
                $table->string('lead_assignment_trigger')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->boolean('is_branch_applicable')->default(0);
                $table->timestamps();
            },
            'travel_plan' => function (Blueprint $table) {
                $table->id();
                $table->string('text')->nullable();
                $table->string('code')->nullable();
                $table->unsignedBigInteger('provider_id')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
            'health_plan_type' => function (Blueprint $table) {
                $table->id();
                $table->string('text')->nullable();
                $table->string('type')->nullable();
                $table->unsignedBigInteger('emirates_id')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
            'health_quote_request' => function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->nullable();
                $table->string('code')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->nullable();
                $table->string('mobile_no')->nullable();
                $table->string('source')->nullable();
                $table->unsignedBigInteger('sub_source_id')->nullable();
                $table->string('health_team_type')->nullable();
                $table->decimal('premium', 10, 2)->nullable();
                $table->string('policy_number')->nullable();
                $table->unsignedBigInteger('support_user_id')->nullable();
                $table->unsignedBigInteger('marital_status_id')->nullable();
                $table->unsignedBigInteger('quote_status_id')->nullable();
                $table->unsignedBigInteger('advisor_id')->nullable();
                $table->unsignedBigInteger('previous_advisor_id')->nullable();
                $table->unsignedBigInteger('lead_type_id')->nullable();
                $table->unsignedBigInteger('previous_quote_id')->nullable();
                $table->unsignedBigInteger('salary_band_id')->nullable();
                $table->unsignedBigInteger('member_category_id')->nullable();
                $table->date('policy_expiry_date')->nullable();
                $table->string('renewal_batch')->nullable();
                $table->string('renewal_import_code')->nullable();
                $table->string('previous_quote_policy_number')->nullable();
                $table->decimal('previous_quote_policy_premium', 10, 2)->nullable();
                $table->string('device')->nullable();
                $table->unsignedBigInteger('plan_id')->nullable();
                $table->date('policy_start_date')->nullable();
                $table->date('policy_issuance_date')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('currently_insured_with_id')->nullable();
                $table->unsignedBigInteger('parent_duplicate_quote_id')->nullable();
                $table->boolean('is_ecommerce')->default(0);
                $table->decimal('price_starting_from', 10, 2)->nullable();
                $table->date('policy_booking_date')->nullable();
                $table->string('insurer_quote_number')->nullable();
                $table->unsignedBigInteger('policy_issuance_status_id')->nullable();
                $table->string('policy_issuance_status_other')->nullable();
                $table->timestamp('stale_at')->nullable();
                $table->string('sic_advisor_requested')->nullable();
                $table->string('aml_status')->nullable();
                $table->unsignedBigInteger('insurance_provider_id')->nullable();
                $table->string('insurer_aml_status')->nullable();
                $table->unsignedBigInteger('renewal_batch_id')->nullable();
                $table->date('previous_policy_expiry_date')->nullable();
                $table->date('dob')->nullable();
                $table->unsignedBigInteger('nationality_id')->nullable();
                $table->timestamp('transaction_approved_at')->nullable();
                $table->string('assignment_type')->nullable();
                $table->string('gender')->nullable();
                $table->unsignedBigInteger('emirate_of_your_visa_id')->nullable();
                $table->timestamp('pec_marked_at')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->boolean('is_branch_applicable')->default(0);
                $table->boolean('is_error_email_sent')->default(0);
                $table->timestamps();
            },
            'health_quote_request_detail' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('health_quote_request_id');
                $table->timestamp('advisor_assigned_date')->nullable();
                $table->unsignedBigInteger('lost_reason_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            },
            'lead_allocation' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('quote_type_id')->nullable();
                $table->integer('auto_assignment_count')->default(0);
                $table->integer('manual_assignment_count')->default(0);
                $table->integer('max_capacity')->default(0);
                $table->timestamps();
            },
            'pqa_lead_allocation_config' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('quote_type_id');
                $table->unsignedInteger('max_capacity')->default(100);
                $table->unsignedInteger('allocation_count')->default(0);
                $table->unsignedInteger('auto_assignment_count')->default(0);
                $table->unsignedInteger('manual_assignment_count')->default(0);
                $table->unsignedBigInteger('last_allocated')->nullable();
                $table->unsignedTinyInteger('reset_cap')->default(0);
                $table->timestamps();
                $table->unique(['user_id', 'quote_type_id']);
            },
            'vehicle_driver_details' => function (Blueprint $table) {
                $table->id();
                $table->morphs('quoteable'); // Creates quoteable_id and quoteable_type
                $table->string('driver_eid_number')->nullable();
                $table->string('driver_first_name')->nullable();
                $table->string('driver_last_name')->nullable();
                $table->date('driver_dob')->nullable();
                $table->string('driver_gender')->nullable();
                $table->string('driver_license_number')->nullable();
                $table->string('driver_license_issue_place')->nullable();
                $table->date('driver_license_issue_date')->nullable();
                $table->date('driver_license_expiry_date')->nullable();
                $table->string('vehicle_plate_number')->nullable();
                $table->string('traffic_code_number')->nullable();
                $table->string('vehicle_engine_number')->nullable();
                $table->string('vehicle_color')->nullable();
                $table->date('first_registration_date')->nullable();
                $table->unsignedBigInteger('nationality_id')->nullable();
                $table->string('vehicle_plate_code')->nullable();
                $table->timestamps();
            },
            'cyber_quote_request' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('personal_quote_id')->nullable();
                $table->unsignedBigInteger('emirate_of_registration_id')->nullable();
                $table->unsignedBigInteger('nationality_id')->nullable();
                $table->unsignedBigInteger('coverage_id')->nullable();
                $table->timestamps();
            },
            'quote_tags' => function (Blueprint $table) {
                $table->id();
                $table->string('quote_uuid');
                $table->string('name');
                $table->unsignedBigInteger('quote_type_id');
                $table->timestamps();
            },
            'savings_quote_request' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('personal_quote_id');
                $table->unsignedBigInteger('marital_status_id')->nullable();
                $table->unsignedBigInteger('tenure_id')->nullable();
                $table->unsignedBigInteger('purpose_id')->nullable();
                $table->unsignedBigInteger('currency_id')->nullable();
                $table->decimal('investment_amount', 15, 2)->nullable();
                $table->unsignedBigInteger('investment_criteria_id')->nullable();
                $table->text('additional_notes')->nullable();
                $table->timestamps();
            },
        ]);
    }

    private function ensureBusinessQuoteTables(): void
    {
        SchemaUtils::ensureTables([
            'business_quote_request' => function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique()->nullable();
                $table->string('code')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->nullable();
                $table->string('mobile_no')->nullable();
                $table->string('source')->nullable();
                $table->unsignedBigInteger('advisor_id')->nullable();
                $table->unsignedBigInteger('support_user_id')->nullable();
                $table->unsignedBigInteger('pq_advisor_id')->nullable();
                $table->unsignedBigInteger('quote_status_id')->nullable();
                $table->unsignedBigInteger('payment_status_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('business_type_of_insurance_id')->nullable();
                $table->unsignedBigInteger('emirate_of_registration_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedBigInteger('nationality_id')->nullable();
                $table->unsignedBigInteger('insurance_provider_id')->nullable();
                $table->unsignedBigInteger('previous_advisor_id')->nullable();
                $table->boolean('is_branch_applicable')->default(0);
                $table->string('company_name')->nullable();
                $table->integer('number_of_employees')->nullable();
                $table->text('brief_details')->nullable();
                $table->string('policy_number')->nullable();
                $table->decimal('premium', 15, 2)->nullable();
                $table->date('policy_booking_date')->nullable();
                $table->date('policy_expiry_date')->nullable();
                $table->string('assignment_type')->nullable();
                $table->string('aml_status')->nullable();
                $table->string('insurer_aml_status')->nullable();
                $table->unsignedBigInteger('sub_source_id')->nullable();
                $table->unsignedBigInteger('sub_source_options_id')->nullable();
                $table->unsignedBigInteger('transaction_type_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            },
        ]);
    }

    /**
     * Tables used by EmbeddedTransactionRepository::fetchFindEmbededTransactionWithDetails()
     * (car_quote_request is in ensureQuoteTables; car_make, car_model, users, car_plan, insurance_provider exist elsewhere).
     */
    private function ensureEmbeddedProductTables(): void
    {
        SchemaUtils::ensureTables([
            'embedded_products' => function (Blueprint $table) {
                $table->id();
                $table->string('short_code')->nullable();
                $table->unsignedBigInteger('insurance_provider_id')->nullable();
                $table->string('product_name')->nullable();
                $table->string('display_name')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
                $table->softDeletes();
            },
            'embedded_product_options' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('embedded_product_id');
                $table->decimal('price', 10, 2)->nullable();
                $table->string('variant')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
                $table->softDeletes();
            },
            'embedded_transactions' => function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->unsignedBigInteger('quote_request_id')->nullable();
                $table->string('quote_request_type')->nullable();
                $table->unsignedBigInteger('quote_type_id')->nullable();
                $table->unsignedBigInteger('product_id')->nullable();
                $table->string('certificate_number')->nullable();
                $table->string('tax_invoice_no')->nullable();
                $table->string('tax_invoice_buyer_no')->nullable();
                $table->boolean('is_selected')->default(false);
                $table->unsignedBigInteger('payment_status_id')->nullable();
                $table->string('policy_status')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
                $table->softDeletes();
            },
        ]);
    }

    private function ensureApplicationStorageTable(): void
    {
        SchemaUtils::ensureTable('application_storage', function (Blueprint $table) {
            $table->id();
            $table->string('key_name')->unique();
            $table->text('value')->nullable();
            $table->boolean('is_active')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * PCP (Private Client) config table required when CarQuoteObserver (and other quote observers)
     * dispatch PrivateClientUpdatedEvent with PolicyBooked – ApplyPrivateClientTagListener
     * uses PrivateClientConfigService which queries pcp_config.
     */
    private function ensurePcpConfigTable(): void
    {
        SchemaUtils::ensureTable('pcp_config', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quote_type_id');
            $table->string('field_name')->nullable();
            $table->string('operator')->nullable();
            $table->boolean('status')->default(1);
            $table->unsignedInteger('version')->nullable();
            $table->string('active_version')->default('0');
            $table->string('quote_type')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function ensureTeamTables(): void
    {
        SchemaUtils::ensureTables([
            'teams' => function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->nullable();
                // TeamHierarchyTrait::getAllProducts() relies on these columns.
                $table->unsignedTinyInteger('type')->nullable();
                $table->boolean('is_active')->default(1);
                $table->unsignedBigInteger('parent_team_id')->nullable();
                $table->decimal('min_price', 15, 2)->nullable();
                $table->decimal('max_price', 15, 2)->nullable();
                $table->boolean('allocation_threshold_enabled')->default(0);
                $table->timestamps();
            },
            'user_team' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('team_id');
                $table->unsignedBigInteger('manager_id')->nullable();
                $table->timestamps();
            },
            'user_products' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('product_id');
                $table->timestamps();
            },
        ]);
    }

    private function ensureUserManagementTables(): void
    {
        SchemaUtils::ensureTables([
            // Pivot: user_manager (subordinates <-> managers)
            'user_manager' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('manager_id');
                $table->timestamps();
            },
            // Departments + pivot used by DepartmentService::syncUserDepartments()
            'departments' => function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
            'user_departments' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('department_id');
                $table->timestamps();
            },
            // Pivot used by User::businessTypes()->sync()
            'business_type_of_insurance_user' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('business_type_of_insurance_id');
                $table->timestamps();
            },
            // Used in update() (sync via inserts)
            'user_products' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('product_id');
                $table->timestamps();
            },
        ]);
    }

    private function ensureQueueTables(): void
    {
        SchemaUtils::ensureTables([
            // QUEUE_CONNECTION=database in phpunit.xml expects these to exist.
            'jobs' => function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            },
            'failed_jobs' => function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            },
        ]);
    }

    private function ensurePaymentTables(): void
    {
        SchemaUtils::ensureTables([
            'payment_status' => function (Blueprint $table) {
                $table->id();
                $table->string('text')->nullable();
                $table->string('text_ar')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
            'payments' => function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->unsignedBigInteger('payment_status_id')->nullable();
                $table->unsignedBigInteger('plan_id')->nullable();
                $table->unsignedBigInteger('insurance_provider_id')->nullable();
                $table->decimal('captured_amount', 10, 2)->default(0);
                $table->timestamp('captured_at')->nullable();
                $table->timestamp('authorized_at')->nullable();
                $table->string('payment_methods_code')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->boolean('is_approved')->default(0);
                $table->string('reference')->nullable();
                $table->string('collection_type')->nullable();
                $table->string('payment_link')->nullable();
                $table->integer('total_payments')->nullable();
                $table->string('credit_approval')->nullable();
                $table->string('frequency')->nullable();
                $table->string('discount_type')->nullable();
                $table->string('discount_reason')->nullable();
                $table->string('custom_reason')->nullable();
                $table->text('notes')->nullable();
                $table->decimal('total_price', 10, 2)->nullable();
                $table->date('collection_date')->nullable();
                $table->string('payer_name')->nullable();
                $table->string('paid_by')->nullable();
                $table->decimal('discount_value', 10, 2)->default(0);
                $table->decimal('total_amount', 10, 2)->nullable();
                $table->string('payment_allocation_status')->nullable();
                $table->unsignedBigInteger('decline_reason_id')->nullable();
                $table->string('decline_custom_reason')->nullable();
                $table->string('discount_custom_reason')->nullable();
                $table->decimal('commission_vat', 10, 2)->nullable();
                $table->decimal('commission_without_vat', 10, 2)->nullable();
                $table->decimal('commission_vat_applicable', 10, 2)->nullable();
                $table->decimal('commission_vat_not_applicable', 10, 2)->nullable();
                $table->decimal('commission', 10, 2)->nullable();
                $table->string('tax_invoice_number')->nullable();
                $table->string('broker_invoice_number')->nullable();
                $table->date('insurer_invoice_date')->nullable();
                $table->text('invoice_description')->nullable();
                $table->string('insurer_payment_link')->nullable();
                $table->string('insurer_tax_number')->nullable();
                $table->string('transaction_payment_status')->nullable();
                $table->string('insurer_commmission_invoice_number')->nullable();
                $table->decimal('commmission_percentage', 5, 2)->nullable();
                $table->unsignedBigInteger('send_update_log_id')->nullable();
                $table->date('policy_expiry_date')->nullable();
                $table->decimal('price_vat_applicable', 10, 2)->nullable();
                $table->decimal('price_vat', 10, 2)->nullable();
                $table->string('commission_based_on_currency')->nullable();
                $table->decimal('exchange_rate', 10, 4)->nullable();
                $table->string('currency')->nullable();
                $table->string('sage_commission_receipt_id')->nullable();
                $table->unsignedBigInteger('payment_gateway_id')->nullable();
                $table->morphs('paymentable'); // Creates paymentable_id and paymentable_type
                $table->timestamps();
                $table->softDeletes();
            },
            'payment_splits' => function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->integer('sr_no')->nullable();
                $table->string('payment_method')->nullable();
                $table->text('check_detail')->nullable();
                $table->decimal('payment_amount', 10, 2)->nullable();
                $table->date('due_date')->nullable();
                $table->unsignedBigInteger('payment_status_id')->nullable();
                $table->decimal('collection_amount', 10, 2)->nullable();
                $table->string('bank_reference_number')->nullable();
                $table->unsignedBigInteger('decline_reason_id')->nullable();
                $table->string('insurer_payment_link')->nullable();
                $table->string('decline_custom_reason')->nullable();
                $table->string('sage_reciept_id')->nullable();
                $table->string('digital_wallet')->nullable();
                $table->string('payment_link')->nullable();
                $table->timestamp('payment_link_created_at')->nullable();
                $table->string('payment_allocation_status')->nullable();
                $table->timestamp('captured_at')->nullable();
                $table->timestamp('authorized_at')->nullable();
                $table->boolean('is_approved')->default(0);
                $table->string('reference')->nullable();
                $table->decimal('discount_value', 10, 2)->default(0);
                $table->unsignedBigInteger('verified_by')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->decimal('price_vat_applicable', 10, 2)->nullable();
                $table->decimal('price_vat', 10, 2)->nullable();
                $table->decimal('commission_vat_applicable', 10, 2)->nullable();
                $table->decimal('commission_vat', 10, 2)->nullable();
                $table->string('insurer_receipt_number')->nullable();
                $table->string('sage_ap_payment_receipt_id')->nullable();
                $table->unsignedBigInteger('payment_gateway_id')->nullable();
                $table->string('cc_payment_gateway')->nullable();
                $table->timestamps();
                $table->softDeletes();
            },
            'payment_status_log' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('current_payment_status_id')->nullable();
                $table->unsignedBigInteger('previous_payment_status_id')->nullable();
                $table->string('payment_code')->nullable();
                $table->timestamps();
            },
            'payment_charges' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payment_split_id'); // made NOT NULL as per model contract
                $table->string('transaction_id')->nullable();
                $table->string('charge_id')->nullable();
                $table->decimal('amount', 15, 2)->nullable();
                $table->timestamps();
            },
        ]);
    }

    private function ensureLookupTables(): void
    {
        SchemaUtils::ensureTables([
            'lookups' => function (Blueprint $table) {
                $table->id();
                $table->string('key')->nullable();
                $table->string('text')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
            'activities' => function (Blueprint $table) {
                $table->id();
                $table->string('activity_type')->nullable();
                $table->integer('reminders_sent')->default(0);
                $table->integer('status')->default(0);
                $table->unsignedBigInteger('assignee_id')->nullable();
                $table->timestamps();
            },
        ]);
    }

    private function ensureCustomerTables(): void
    {
        SchemaUtils::ensureTables([
            'customer' => function (Blueprint $table) {
                $table->id();
                $table->string('emirates_id_number')->nullable();
                $table->date('emirates_id_expiry_date')->nullable();
                $table->unsignedBigInteger('nationality_id')->nullable();
                $table->date('dob')->nullable();
                $table->string('code')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->nullable();
                $table->string('mobile_no')->nullable();
                $table->text('address')->nullable();
                $table->boolean('pcp_tag')->default(0);
                $table->timestamps();
                $table->softDeletes();
            },
            'insured' => function (Blueprint $table) {
                $table->id();
                $table->string('insurable_type')->nullable(); // Polymorphic relationship
                $table->unsignedBigInteger('insurable_id')->nullable(); // Polymorphic relationship
                $table->string('customer_type')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->date('dob')->nullable();
                $table->unsignedBigInteger('nationality_id')->nullable();
                $table->string('gender')->nullable();
                $table->string('id_type')->nullable();
                $table->string('id_number')->nullable();
                $table->string('code')->nullable();
                $table->unsignedBigInteger('customer_details_id')->nullable();
                $table->unsignedBigInteger('emirate_of_registration_id')->nullable();
                $table->string('email')->nullable();
                $table->string('mobile_no')->nullable();
                $table->timestamps();
                $table->softDeletes();
            },
            'insured_kyc' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('insured_id');
                $table->unsignedBigInteger('customer_details_id')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('mobile_no')->nullable();
                $table->string('email')->nullable();
                $table->string('website')->nullable();
                $table->string('legal_structure')->nullable();
                $table->string('country_of_corporation')->nullable();
                $table->string('registered_address')->nullable();
                $table->string('communication_address')->nullable();
                $table->string('country_of_residence')->nullable();
                $table->string('place_of_birth')->nullable();
                $table->string('residential_status')->nullable();
                $table->string('residential_address')->nullable();
                $table->string('id_type')->nullable();
                $table->string('id_number')->nullable();
                $table->date('id_issuance_date')->nullable();
                $table->date('id_expiry_date')->nullable();
                $table->string('issuance_place')->nullable();
                $table->string('id_issuance_authority')->nullable();
                $table->string('source_of_income')->nullable();
                $table->string('employer_company_name')->nullable();
                $table->string('job_title')->nullable();
                $table->string('employment_sector')->nullable();
                $table->string('position_in_company')->nullable();
                $table->string('trade_license_no')->nullable();
                $table->boolean('pep')->nullable();
                $table->boolean('financial_sanctions')->nullable();
                $table->boolean('dual_nationality')->nullable();
                $table->string('customer_tenure')->nullable();
                $table->string('transaction_volume')->nullable();
                $table->string('transaction_activities')->nullable();
                $table->string('transaction_pattern')->nullable();
                $table->string('premium_tenure')->nullable();
                $table->string('mode_of_contact')->nullable();
                $table->string('mode_of_delivery')->nullable();
                $table->string('risk_score')->nullable();
                $table->boolean('in_sanction_list')->nullable();
                $table->boolean('deal_sanction_list')->nullable();
                $table->boolean('is_operation_high_risk')->nullable();
                $table->boolean('is_partner')->nullable();
                $table->boolean('in_adverse_media')->nullable();
                $table->boolean('is_owner_pep')->nullable();
                $table->boolean('is_controlling_pep')->nullable();
                $table->boolean('is_sanction_match')->nullable();
                $table->boolean('in_fatf')->nullable();
                $table->string('industry_type')->nullable();
                $table->string('manager_name')->nullable();
                $table->string('manager_nationality')->nullable();
                $table->date('manager_dob')->nullable();
                $table->string('manager_position')->nullable();
                $table->boolean('is_owner_high_risk')->nullable();
                $table->timestamps();
            },
            'customer_insured' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('quote_type_id');
                $table->unsignedBigInteger('quote_request_id');
                $table->unsignedBigInteger('insured_id');
                $table->unsignedBigInteger('customer_id');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
                $table->softDeletes();
            },
            'customer_members' => function (Blueprint $table) {
                $table->id();
                $table->string('quote_type'); // Polymorphic: model class name
                $table->unsignedBigInteger('quote_id'); // Polymorphic: model ID
                $table->string('customer_type')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->date('dob')->nullable();
                $table->unsignedBigInteger('nationality_id')->nullable();
                $table->string('gender')->nullable();
                $table->string('relation_code')->nullable();
                $table->unsignedBigInteger('emirate_of_your_visa_id')->nullable();
                $table->unsignedBigInteger('member_category_id')->nullable();
                $table->unsignedBigInteger('salary_band_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            },
            'insured_kyc' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('insured_id');
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('id_type')->nullable();
                $table->string('id_number')->nullable();
                $table->timestamps();
            },
        ]);
    }

    private function ensureDocumentTables(): void
    {
        SchemaUtils::ensureTables([
            'quote_documents' => function (Blueprint $table) {
                $table->id();
                $table->string('doc_name')->nullable();
                $table->string('doc_url')->nullable();
                $table->string('doc_mime_type')->nullable();
                $table->string('document_type_code')->nullable();
                $table->string('document_type_text')->nullable();
                $table->unsignedBigInteger('document_type_id')->nullable();
                $table->string('doc_uuid')->nullable();
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->string('original_name')->nullable();
                $table->unsignedBigInteger('member_detail_id')->nullable();
                $table->string('payment_split_type')->nullable();
                $table->unsignedBigInteger('payment_split_id')->nullable();
                $table->string('watermarked_doc_name')->nullable();
                $table->string('watermarked_doc_url')->nullable();
                $table->string('document_category')->nullable();
                $table->string('insurer_document_link')->nullable();
                $table->boolean('is_ocr_processed')->default(false);
                $table->unsignedBigInteger('quote_id')->nullable();
                $table->morphs('quote_documentable'); // Creates quote_documentable_id and quote_documentable_type
                $table->boolean('is_manual_override')->default(false);
                $table->text('override_remarks')->nullable();
                $table->boolean('is_restricted_internal_document')->default(false);
                $table->timestamps();
                $table->softDeletes();
            },
            'document_types' => function (Blueprint $table) {
                $table->id();
                $table->string('code');
                $table->string('text');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(1);
                $table->boolean('receive_from_customer')->default(0);
                $table->unsignedBigInteger('quote_type_id')->nullable();
                $table->string('registration_type')->nullable();
                $table->string('vehicle_use')->nullable();
                $table->integer('sort_order')->nullable();
                $table->string('accepted_files')->nullable();
                $table->unsignedInteger('max_size')->nullable();
                $table->unsignedInteger('max_files')->nullable();
                $table->string('category')->nullable();
                $table->boolean('is_required')->default(0);
                $table->boolean('is_required_for_send_policy')->default(0);
                $table->string('folder_path')->nullable();
                $table->boolean('send_to_customer')->default(0);
                $table->boolean('is_restricted_internal_document')->default(false);
                $table->unsignedBigInteger('business_type_of_insurance_id')->nullable();
                $table->string('business_type_of_customer')->nullable();
                $table->timestamps();
                $table->unique(['code', 'quote_type_id', 'business_type_of_insurance_id', 'business_type_of_customer']);
            },
            'generic_document_types' => function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('text');
                $table->text('description')->nullable();
                $table->timestamps();
            },
            'generic_documents' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('generic_document_type_id')->nullable();
                $table->string('uuid')->nullable();
                $table->string('documentable_type');
                $table->unsignedBigInteger('documentable_id');
                $table->integer('quote_type_id')->nullable();
                $table->string('name')->nullable();
                $table->string('path')->nullable();
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->unsignedBigInteger('insurance_provider_id')->nullable();
                $table->unsignedBigInteger('business_type_of_insurance_id')->nullable();
                $table->timestamps();
            },
        ]);
    }

    private function ensureInsuranceProviderTables(): void
    {
        SchemaUtils::ensureTables([
            'insurance_provider' => function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text')->nullable();
                $table->string('text_lms')->nullable();
                $table->integer('sort_order')->nullable();
                $table->boolean('is_active')->default(1);
                $table->boolean('is_deleted')->default(0);
                $table->unsignedBigInteger('payment_gateway_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            },
            'insurance_provider_quote_type' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('insurance_provider_id');
                $table->unsignedBigInteger('quote_type_id');
                $table->timestamps();
            },
            'business_type_of_insurance' => function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
        ]);
    }

    private function ensureGroupMedicalFormDropdownTables(): void
    {
        SchemaUtils::ensureTables([
            'company_activity_type' => function (Blueprint $table) {
                $table->id();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
            'health_third_party_administrator' => function (Blueprint $table) {
                $table->id();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
            'group_medical_networks' => function (Blueprint $table) {
                $table->id();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
            'group_medical_category' => function (Blueprint $table) {
                $table->id();
                $table->string('text');
                $table->unsignedInteger('sort_order')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
        ]);
    }

    private function ensurePolicyIssuanceTables(): void
    {
        SchemaUtils::ensureTables([
            'policy_issuance' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('insurance_provider_id')->nullable();
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->string('quote_type');
                $table->string('status')->nullable();
                $table->string('completed_step')->nullable();
                $table->text('message')->nullable();
                $table->unsignedInteger('retry_count')->nullable()->default(0);
                $table->timestamps();
            },
            'policy_issuance_logs' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('policy_issuance_id');
                $table->string('model_type')->nullable();
                $table->unsignedBigInteger('model_id')->nullable();
                $table->string('step')->nullable();
                $table->string('status')->nullable();
                $table->text('payload')->nullable();
                $table->text('response')->nullable();
                $table->string('endPoint')->nullable();
                $table->timestamps();
            },
            'policy_issuance_status' => function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
        ]);

        SchemaUtils::ensureColumns([
            'policy_issuance' => [
                'retry_count' => fn (Blueprint $table) => $table->unsignedInteger('retry_count')->nullable()->default(0),
            ],
        ]);
    }

    private function ensureSendUpdateTables(): void
    {
        SchemaUtils::ensureTables([
            'send_update_logs' => function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('quote_uuid')->nullable();
                $table->unsignedBigInteger('quote_type_id');
                $table->string('status')->nullable();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->unsignedBigInteger('option_id')->nullable();
                $table->timestamps();
            },
        ]);
    }

    private function ensureSageTables(): void
    {
        SchemaUtils::ensureTables([
            'sage_processes' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('insurance_provider_id')->nullable();
                $table->string('model_type')->nullable();
                $table->unsignedBigInteger('model_id')->nullable();
                $table->text('request')->nullable();
                $table->text('message')->nullable();
                $table->string('status')->nullable();
                $table->timestamps();
                $table->softDeletes(); // BaseModel uses SoftDeletes trait
            },
            'sage_api_logs' => function (Blueprint $table) {
                $table->id();
                $table->string('section_type')->nullable();
                $table->unsignedBigInteger('section_id')->nullable();
                $table->text('request')->nullable();
                $table->text('response')->nullable();
                $table->string('status')->nullable();
                $table->string('sage_request_type')->nullable();
                $table->integer('step')->nullable();
                $table->timestamps();
            },
        ]);
    }

    private function ensureCustomerAdditionalContactTables(): void
    {
        SchemaUtils::ensureTables([
            'customer_additional_contact' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('key')->nullable();
                $table->string('value')->nullable();
                $table->boolean('wa_opt_in')->default(0);
                $table->timestamps();
            },
        ]);
    }

    private function ensureEPLogsTables(): void
    {
        SchemaUtils::ensureTables([
            'ep_logs' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('embedded_transaction_id');
                $table->string('event');
                $table->text('values')->nullable();
                $table->morphs('loggable');
                $table->timestamps();

                $table->index('embedded_transaction_id');
            },
        ]);
    }

    private function ensureCatARevivalBuyLeadSupport(): void
    {
        SchemaUtils::ensureColumns([
            'car_quote_request' => [
                'car_value_tier' => fn (Blueprint $table) => $table->decimal('car_value_tier', 15, 2)->nullable(),
            ],
        ]);
        SchemaUtils::ensureTables([
            'buy_lead_configuration_nationalities' => function (Blueprint $table) {
                $table->id();
                $table->string('quote_type');
                $table->unsignedBigInteger('nationality_id');
                $table->timestamps();
            },
        ]);
    }
}
