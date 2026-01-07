<?php

namespace Tests\Helpers;

use Illuminate\Support\Facades\Schema;

class TestSchemaCreator
{
    /**
     * Create minimal required tables for LifeQuote tests.
     */
    public static function createMinimalSchema(): void
    {
        // Create audits table if it doesn't exist (for Laravel Auditing)
        if (! Schema::connection('sqlite')->hasTable('audits')) {
            Schema::connection('sqlite')->create('audits', function ($table) {
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
        if (! Schema::connection('sqlite')->hasTable('users')) {
            Schema::connection('sqlite')->create('users', function ($table) {
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
        if (! Schema::connection('sqlite')->hasTable('roles')) {
            Schema::connection('sqlite')->create('roles', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });
        }

        // Create model_has_roles table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('model_has_roles')) {
            Schema::connection('sqlite')->create('model_has_roles', function ($table) {
                $table->id();
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->timestamps();
            });
        }

        // Create permissions table if it doesn't exist (for Spatie permissions)
        if (! Schema::connection('sqlite')->hasTable('permissions')) {
            Schema::connection('sqlite')->create('permissions', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });
        }

        // Create model_has_permissions table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('model_has_permissions')) {
            Schema::connection('sqlite')->create('model_has_permissions', function ($table) {
                $table->id();
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->timestamps();
            });
        }

        // Create role_has_permissions table if it doesn't exist (pivot table for Spatie permissions)
        if (! Schema::connection('sqlite')->hasTable('role_has_permissions')) {
            Schema::connection('sqlite')->create('role_has_permissions', function ($table) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->primary(['permission_id', 'role_id']);
            });
        }

        // Create nationality table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('nationality')) {
            Schema::connection('sqlite')->create('nationality', function ($table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // Create currency_type table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('currency_type')) {
            Schema::connection('sqlite')->create('currency_type', function ($table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create marital_status table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('marital_status')) {
            Schema::connection('sqlite')->create('marital_status', function ($table) {
                $table->id();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create life_insurance_purpose table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('life_insurance_purpose')) {
            Schema::connection('sqlite')->create('life_insurance_purpose', function ($table) {
                $table->id();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create life_number_of_year table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('life_number_of_year')) {
            Schema::connection('sqlite')->create('life_number_of_year', function ($table) {
                $table->id();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create personal_quotes table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('personal_quotes')) {
            Schema::connection('sqlite')->create('personal_quotes', function ($table) {
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
        if (! Schema::connection('sqlite')->hasTable('life_quote_request')) {
            Schema::connection('sqlite')->create('life_quote_request', function ($table) {
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
        if (! Schema::connection('sqlite')->hasTable('application_storage')) {
            Schema::connection('sqlite')->create('application_storage', function ($table) {
                $table->id();
                $table->string('key_name')->unique();
                $table->text('value')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // Create teams table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('teams')) {
            Schema::connection('sqlite')->create('teams', function ($table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        // Create user_team table if it doesn't exist (pivot table)
        if (! Schema::connection('sqlite')->hasTable('user_team')) {
            Schema::connection('sqlite')->create('user_team', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('team_id');
                $table->timestamps();
            });
        }

        // Create payments table if it doesn't exist (minimal structure)
        if (! Schema::connection('sqlite')->hasTable('payments')) {
            Schema::connection('sqlite')->create('payments', function ($table) {
                $table->id();
                $table->string('code')->nullable();
                $table->unsignedBigInteger('payment_status_id')->nullable();
                $table->morphs('paymentable');
                $table->timestamps();
            });
        }

        // Create lookups table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('lookups')) {
            Schema::connection('sqlite')->create('lookups', function ($table) {
                $table->id();
                $table->string('key')->nullable();
                $table->string('text')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create policy_issuance_status table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('policy_issuance_status')) {
            Schema::connection('sqlite')->create('policy_issuance_status', function ($table) {
                $table->id();
                $table->string('name')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create activities table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('activities')) {
            Schema::connection('sqlite')->create('activities', function ($table) {
                $table->id();
                $table->string('activity_type')->nullable();
                $table->integer('reminders_sent')->default(0);
                $table->integer('status')->default(0);
                $table->unsignedBigInteger('assignee_id')->nullable();
                $table->timestamps();
            });
        }

        // Create customer table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('customer')) {
            Schema::connection('sqlite')->create('customer', function ($table) {
                $table->id();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->nullable();
                $table->string('mobile_no')->nullable();
                $table->timestamps();
            });
        }

        // Create quote_status table if it doesn't exist (needed for car_quote_request)
        if (! Schema::connection('sqlite')->hasTable('quote_status')) {
            Schema::connection('sqlite')->create('quote_status', function ($table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('code')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            });
        }

        // Create car_quote_request table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('car_quote_request')) {
            Schema::connection('sqlite')->create('car_quote_request', function ($table) {
                $table->id();
                $table->string('uuid')->nullable();
                $table->string('code')->nullable();
                $table->boolean('is_ecommerce')->default(false);
                $table->decimal('car_value', 15, 2)->nullable();
                $table->unsignedBigInteger('car_make_id')->nullable();
                $table->unsignedBigInteger('car_model_id')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->nullable();
                $table->string('mobile_no')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('quote_status_id')->nullable();
                $table->timestamp('quote_status_date')->nullable();
                $table->string('registration_type')->nullable();
                $table->string('source')->nullable();
                $table->unsignedBigInteger('nationality_id')->nullable();
                $table->unsignedBigInteger('tier_id')->nullable();
                $table->unsignedBigInteger('quote_batch_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->dateTime('policy_expiry_date')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Create quote_sync table if it doesn't exist (needed for syncQuote)
        if (! Schema::connection('sqlite')->hasTable('quote_sync')) {
            Schema::connection('sqlite')->create('quote_sync', function ($table) {
                $table->id();
                $table->string('quote_uuid')->nullable();
                $table->unsignedBigInteger('quote_type_id')->nullable();
                $table->text('updated_fields')->nullable();
                $table->boolean('is_synced')->default(false);
                $table->string('status')->nullable();
                $table->text('error')->nullable();
                $table->timestamp('synced_at')->nullable();
                $table->timestamps();
            });
        }
    }
}
