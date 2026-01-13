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
        $this->ensureTeamTables();
        $this->ensureLookupTables();
        $this->ensureCustomerTables();
        $this->ensureInsuranceProviderTables();
        $this->ensureQuoteTables();
        $this->ensurePaymentTables();
        $this->ensureDocumentTables();
        $this->ensurePolicyIssuanceTables();
        $this->ensureSageTables();
        $this->ensureCustomerAdditionalContactTables();
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
                $table->integer('status')->nullable();
                $table->timestamp('logout_at')->nullable();
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
                $table->string('short_code')->unique();
                $table->string('code');
                $table->boolean('is_active')->default(1);
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
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->string('previous_quote_policy_number')->nullable();
                $table->date('previous_policy_expiry_date')->nullable();
                $table->date('policy_expiry_date')->nullable();
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
                $table->integer('insurer_api_status_id')->nullable();
                $table->integer('api_issuance_status_id')->nullable();
                $table->boolean('policy_issuance_automation_enabled')->default(false);
                $table->timestamps();
                $table->softDeletes(); // BaseModel uses SoftDeletes trait
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
                $table->timestamps();
            },
            'car_plan' => function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text')->nullable();
                $table->string('plan_name')->nullable();
                $table->boolean('is_active')->default(1);
                $table->unsignedBigInteger('provider_id')->nullable();
                $table->timestamps();
                $table->softDeletes(); // BaseModel uses SoftDeletes trait
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

    private function ensureTeamTables(): void
    {
        SchemaUtils::ensureTables([
            'teams' => function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            },
            'user_team' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('team_id');
                $table->timestamps();
            },
        ]);
    }

    private function ensurePaymentTables(): void
    {
        SchemaUtils::ensureTables([
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
                $table->boolean('is_active')->default(1);
                $table->unsignedBigInteger('payment_gateway_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            },
            'business_type_of_insurance' => function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('text');
                $table->timestamps();
            },
        ]);
    }

    private function ensurePolicyIssuanceTables(): void
    {
        SchemaUtils::ensureTables([
            'policy_issuance' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('insurance_provider_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->string('quote_type');
                $table->string('status')->nullable();
                $table->string('completed_step')->nullable();
                $table->text('message')->nullable();
                $table->timestamps();
            },
            'policy_issuance_log' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('policy_issuance_id');
                $table->string('step')->nullable();
                $table->string('status')->nullable();
                $table->text('request_payload')->nullable();
                $table->text('response_payload')->nullable();
                $table->string('api_url')->nullable();
                $table->timestamps();
            },
            'policy_issuance_status' => function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->boolean('is_active')->default(1);
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
}
