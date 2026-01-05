<?php

namespace Tests\Helpers;

use Illuminate\Support\Facades\DB;

class TestSchemaCreator
{
    /**
     * Create minimal required tables for LifeQuote tests.
     */
    public static function createMinimalSchema(): void
    {
        $schema = DB::connection('sqlite')->getSchemaBuilder();

        // Create audits table if it doesn't exist (for Laravel Auditing)
        if (! $schema->hasTable('audits')) {
            $schema->create('audits', function ($table) {
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
            });
        }

        // Create users table if it doesn't exist
        if (! $schema->hasTable('users')) {
            $schema->create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('remember_token')->nullable();
                $table->timestamps();
            });
        }

        // Create roles table if it doesn't exist (for Spatie permissions)
        if (! $schema->hasTable('roles')) {
            $schema->create('roles', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });
        }

        // Create model_has_roles table if it doesn't exist
        if (! $schema->hasTable('model_has_roles')) {
            $schema->create('model_has_roles', function ($table) {
                $table->id();
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->timestamps();
            });
        }

        // Create permissions table if it doesn't exist (for Spatie permissions)
        if (! $schema->hasTable('permissions')) {
            $schema->create('permissions', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });
        }

        // Create model_has_permissions table if it doesn't exist
        if (! $schema->hasTable('model_has_permissions')) {
            $schema->create('model_has_permissions', function ($table) {
                $table->id();
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->timestamps();
            });
        }

        // Create role_has_permissions table if it doesn't exist (pivot table for Spatie permissions)
        if (! $schema->hasTable('role_has_permissions')) {
            $schema->create('role_has_permissions', function ($table) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->primary(['permission_id', 'role_id']);
            });
        }

        // Create nationality table if it doesn't exist
        if (! $schema->hasTable('nationality')) {
            $schema->create('nationality', function ($table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // Create currency_type table if it doesn't exist
        if (! $schema->hasTable('currency_type')) {
            $schema->create('currency_type', function ($table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create marital_status table if it doesn't exist
        if (! $schema->hasTable('marital_status')) {
            $schema->create('marital_status', function ($table) {
                $table->id();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create life_insurance_purpose table if it doesn't exist
        if (! $schema->hasTable('life_insurance_purpose')) {
            $schema->create('life_insurance_purpose', function ($table) {
                $table->id();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create life_number_of_year table if it doesn't exist
        if (! $schema->hasTable('life_number_of_year')) {
            $schema->create('life_number_of_year', function ($table) {
                $table->id();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create personal_quotes table if it doesn't exist
        if (! $schema->hasTable('personal_quotes')) {
            $schema->create('personal_quotes', function ($table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->string('code')->nullable();
                $table->unsignedBigInteger('quote_type_id');
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
                $table->timestamps();
            });
        }

        // Create life_quote_request table if it doesn't exist
        if (! $schema->hasTable('life_quote_request')) {
            $schema->create('life_quote_request', function ($table) {
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
            });
        }

        // Create application_storage table if it doesn't exist
        if (! $schema->hasTable('application_storage')) {
            $schema->create('application_storage', function ($table) {
                $table->id();
                $table->string('key_name')->unique();
                $table->text('value')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // Create teams table if it doesn't exist
        if (! $schema->hasTable('teams')) {
            $schema->create('teams', function ($table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        // Create user_team table if it doesn't exist (pivot table)
        if (! $schema->hasTable('user_team')) {
            $schema->create('user_team', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('team_id');
                $table->timestamps();
            });
        }

        // Create payments table if it doesn't exist (minimal structure)
        if (! $schema->hasTable('payments')) {
            $schema->create('payments', function ($table) {
                $table->id();
                $table->string('code')->nullable();
                $table->unsignedBigInteger('payment_status_id')->nullable();
                $table->morphs('paymentable');
                $table->timestamps();
            });
        }

        // Create lookups table if it doesn't exist
        if (! $schema->hasTable('lookups')) {
            $schema->create('lookups', function ($table) {
                $table->id();
                $table->string('key')->nullable();
                $table->string('text')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create policy_issuance_status table if it doesn't exist
        if (! $schema->hasTable('policy_issuance_status')) {
            $schema->create('policy_issuance_status', function ($table) {
                $table->id();
                $table->string('name')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create activities table if it doesn't exist
        if (! $schema->hasTable('activities')) {
            $schema->create('activities', function ($table) {
                $table->id();
                $table->string('activity_type')->nullable();
                $table->integer('reminders_sent')->default(0);
                $table->integer('status')->default(0);
                $table->unsignedBigInteger('assignee_id')->nullable();
                $table->timestamps();
            });
        }

        // Create insured table if it doesn't exist
        if (! $schema->hasTable('insured')) {
            $schema->create('insured', function ($table) {
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
            });
        }

        // Create insured_kyc table if it doesn't exist
        if (! $schema->hasTable('insured_kyc')) {
            $schema->create('insured_kyc', function ($table) {
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
            });
        }
    }
}
