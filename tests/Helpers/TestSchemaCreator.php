<?php

namespace Tests\Helpers;

use Illuminate\Support\Facades\Schema;
use Tests\Support\Schema\RenewalsSchema;

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
                $table->string('emirates_id_number')->nullable();
                $table->date('emirates_id_expiry_date')->nullable();
                $table->date('dob')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->nullable();
                $table->string('mobile_no')->nullable();
                $table->text('address')->nullable();
                $table->timestamps();
            });
        }

        // Create insurance_provider table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('insurance_provider')) {
            Schema::connection('sqlite')->create('insurance_provider', function ($table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Create policy_issuance table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('policy_issuance')) {
            Schema::connection('sqlite')->create('policy_issuance', function ($table) {
                $table->id();
                $table->unsignedBigInteger('insurance_provider_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->string('quote_type');
                $table->string('status')->nullable();
                $table->string('completed_step')->nullable();
                $table->text('message')->nullable();
                $table->timestamps();
            });
        }

        // Update payments table to include required fields
        if (Schema::connection('sqlite')->hasTable('payments')) {
            Schema::connection('sqlite')->table('payments', function ($table) {
                if (! Schema::connection('sqlite')->hasColumn('payments', 'total_amount')) {
                    $table->decimal('total_amount', 15, 2)->nullable();
                }
                if (! Schema::connection('sqlite')->hasColumn('payments', 'is_main_lead_payment')) {
                    $table->boolean('is_main_lead_payment')->default(false);
                }
                if (! Schema::connection('sqlite')->hasColumn('payments', 'insurer_invoice_date')) {
                    $table->date('insurer_invoice_date')->nullable();
                }
                if (! Schema::connection('sqlite')->hasColumn('payments', 'insurer_tax_number')) {
                    $table->string('insurer_tax_number')->nullable();
                }
                if (! Schema::connection('sqlite')->hasColumn('payments', 'insurer_commmission_invoice_number')) {
                    $table->string('insurer_commmission_invoice_number')->nullable();
                }
                if (! Schema::connection('sqlite')->hasColumn('payments', 'discount_value')) {
                    $table->decimal('discount_value', 15, 2)->nullable();
                }
                if (! Schema::connection('sqlite')->hasColumn('payments', 'commission_vat_applicable')) {
                    $table->decimal('commission_vat_applicable', 15, 2)->nullable();
                }
                if (! Schema::connection('sqlite')->hasColumn('payments', 'commission_vat_not_applicable')) {
                    $table->decimal('commission_vat_not_applicable', 15, 2)->nullable();
                }
                if (! Schema::connection('sqlite')->hasColumn('payments', 'commission')) {
                    $table->decimal('commission', 15, 2)->nullable();
                }
                if (! Schema::connection('sqlite')->hasColumn('payments', 'commission_vat')) {
                    $table->decimal('commission_vat', 15, 2)->nullable();
                }
                if (! Schema::connection('sqlite')->hasColumn('payments', 'commmission_percentage')) {
                    $table->decimal('commmission_percentage', 5, 2)->nullable();
                }
            });
        }

        // Create device_quote table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('device_quote')) {
            Schema::connection('sqlite')->create('device_quote', function ($table) {
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
            });
        }

        // Create device_quote_request table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('device_quote_request')) {
            Schema::connection('sqlite')->create('device_quote_request', function ($table) {
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
            });
        }

        // Create insured table if it doesn't exist (for latestInsured relationship)
        if (! Schema::connection('sqlite')->hasTable('insured')) {
            Schema::connection('sqlite')->create('insured', function ($table) {
                $table->id();
                $table->morphs('insurable');
                $table->string('id_type')->nullable();
                $table->string('id_number')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->nullable();
                $table->string('mobile_no')->nullable();
                $table->timestamps();
            });
        }

        // Create customer_insured pivot table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('customer_insured')) {
            Schema::connection('sqlite')->create('customer_insured', function ($table) {
                $table->id();
                $table->unsignedBigInteger('quote_request_id');
                $table->unsignedBigInteger('insured_id');
                $table->timestamps();
            });
        }

        // Create quote_documents table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('quote_documents')) {
            Schema::connection('sqlite')->create('quote_documents', function ($table) {
                $table->id();
                $table->morphs('documentable');
                $table->string('document_type')->nullable();
                $table->string('document_path')->nullable();
                $table->string('document_name')->nullable();
                $table->string('document_url')->nullable();
                $table->timestamps();
            });
        }

        // Create payment_splits table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('payment_splits')) {
            Schema::connection('sqlite')->create('payment_splits', function ($table) {
                $table->id();
                $table->unsignedBigInteger('payment_id');
                $table->string('payment_method')->nullable();
                $table->decimal('amount', 15, 2)->nullable();
                $table->timestamps();
            });
        }

        // Create payment_charges table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('payment_charges')) {
            Schema::connection('sqlite')->create('payment_charges', function ($table) {
                $table->id();
                $table->unsignedBigInteger('payment_split_id');
                $table->string('transaction_id')->nullable();
                $table->string('charge_id')->nullable();
                $table->decimal('amount', 15, 2)->nullable();
                $table->timestamps();
            });
        }

        // Create policy_issuance_log table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('policy_issuance_log')) {
            Schema::connection('sqlite')->create('policy_issuance_log', function ($table) {
                $table->id();
                $table->unsignedBigInteger('policy_issuance_id');
                $table->string('step')->nullable();
                $table->string('status')->nullable();
                $table->text('request_payload')->nullable();
                $table->text('response_payload')->nullable();
                $table->string('api_url')->nullable();
                $table->timestamps();
            });
        }
    }

    public static function createRenewalsSchema(): void
    {
        self::createMinimalSchema();

        (new RenewalsSchema)->register();
    }
}
