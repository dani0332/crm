<?php

namespace Tests\Helpers;

use Illuminate\Support\Facades\DB;
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
        // Required for VAT calculations in PaymentObserver and PaymentSplitsObserver
        if (! Schema::connection('sqlite')->hasTable('application_storage')) {
            Schema::connection('sqlite')->create('application_storage', function ($table) {
                $table->id();
                $table->string('key_name')->unique();
                $table->text('value')->nullable();
                $table->boolean('is_active')->default(1);
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

        // Create payments table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('payments')) {
            Schema::connection('sqlite')->create('payments', function ($table) {
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
            });
        }

        // Create payment_splits table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('payment_splits')) {
            Schema::connection('sqlite')->create('payment_splits', function ($table) {
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
            });
        }

        // Create payment_status_log table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('payment_status_log')) {
            Schema::connection('sqlite')->create('payment_status_log', function ($table) {
                $table->id();
                $table->unsignedBigInteger('current_payment_status_id')->nullable();
                $table->unsignedBigInteger('previous_payment_status_id')->nullable();
                $table->string('payment_code')->nullable();
                $table->timestamps();
            });
        }

        // Create quote_documents table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('quote_documents')) {
            Schema::connection('sqlite')->create('quote_documents', function ($table) {
                $table->id();
                $table->string('doc_name')->nullable();
                $table->string('doc_url')->nullable();
                $table->string('doc_mime_type')->nullable();
                $table->string('document_type_code')->nullable();
                $table->string('document_type_text')->nullable();
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
                $table->morphs('quote_documentable'); // Creates quote_documentable_id and quote_documentable_type
                $table->timestamps();
                $table->softDeletes();
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

        // Create pest_test_table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('pest_test_table')) {
            Schema::connection('sqlite')->create('pest_test_table', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->timestamps();
            });
        }

        // Create insurance_provider table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('insurance_provider')) {
            Schema::connection('sqlite')->create('insurance_provider', function ($table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
                $table->softDeletes(); // BaseModel uses SoftDeletes trait
            });
        }

        // Create car_plan table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('car_plan')) {
            Schema::connection('sqlite')->create('car_plan', function ($table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text')->nullable();
                $table->boolean('is_active')->default(1);
                $table->unsignedBigInteger('provider_id')->nullable();
                $table->timestamps();
                $table->softDeletes(); // BaseModel uses SoftDeletes trait
            });
        }

        
        // Create personal_quotes table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('car_quote_request')) {
            Schema::connection('sqlite')->create('car_quote_request', function ($table) {
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
                $table->unsignedBigInteger('advisor_id')->nullable();
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->unsignedBigInteger('updated_by_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable(); // Required for UpdateCustomerEmail listener
                $table->timestamps();
                $table->softDeletes(); // BaseModel uses SoftDeletes trait
            });
        }

        // Create sage_processes table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('sage_processes')) {
            Schema::connection('sqlite')->create('sage_processes', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('insurance_provider_id')->nullable();
                $table->string('model_type')->nullable();
                $table->unsignedBigInteger('model_id')->nullable();
                $table->text('request')->nullable();
                $table->text('message')->nullable();
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }

        // Create sage_api_logs table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('sage_api_logs')) {
            Schema::connection('sqlite')->create('sage_api_logs', function ($table) {
                $table->id();
                $table->string('section_type')->nullable();
                $table->unsignedBigInteger('section_id')->nullable();
                $table->text('request')->nullable();
                $table->text('response')->nullable();
                $table->string('status')->nullable();
                $table->string('sage_request_type')->nullable();
                $table->integer('step')->nullable();
                $table->timestamps();
            });
        }

        // Create quote_sync table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('quote_sync')) {
            Schema::connection('sqlite')->create('quote_sync', function ($table) {
                $table->id();
                $table->string('quote_uuid')->nullable();
                $table->unsignedBigInteger('quote_type_id')->nullable();
                $table->text('updated_fields')->nullable();
                $table->boolean('is_synced')->default(0);
                $table->timestamp('synced_at')->nullable();
                $table->string('status')->nullable();
                $table->text('error')->nullable();
                $table->timestamps();
            });
        }

        // Create car_quote_request_detail table if it doesn't exist
        if (! Schema::connection('sqlite')->hasTable('car_quote_request_detail')) {
            Schema::connection('sqlite')->create('car_quote_request_detail', function ($table) {
                $table->id();
                $table->unsignedBigInteger('car_quote_request_id')->nullable();
                $table->timestamps();
            });
        }

        // Create customer table if it doesn't exist
        // Required by UpdateCustomerEmail listener when QuoteEmailUpdated event is fired
        if (! Schema::connection('sqlite')->hasTable('customer')) {
            Schema::connection('sqlite')->create('customer', function ($table) {
                $table->id();
                $table->string('email')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('mobile_no')->nullable();
                $table->boolean('pcp_tag')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }
}
