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
            'quote_type' =>
            function (Blueprint $table) {
                $table->id();
                $table->string('short_code')->unique();
                $table->string('code');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
            },
            'car_make' =>
            function (Blueprint $table) {
                $table->id();
                $table->string('text');
                $table->string('code')->nullable();
                $table->timestamps();
                $table->softDeletes();
            },
            'car_model' =>
            function (Blueprint $table) {
                $table->id();
                $table->string('text');
                $table->string('car_make_code')->nullable();
                $table->unsignedBigInteger('vehicle_type_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            },
            'nationality' =>
            function (Blueprint $table) {
                $table->id();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
                $table->softDeletes();
            },
            'emirates' =>
            function (Blueprint $table) {
                $table->id();
                $table->string('text');
                $table->boolean('is_active')->default(1);
                $table->timestamps();
                $table->softDeletes();
            },
            'claim_history' =>
            function (Blueprint $table) {
                $table->id();
                $table->string('text');
                $table->timestamps();
                $table->softDeletes();
            },
            'uae_license_held_for' =>
            function (Blueprint $table) {
                $table->id();
                $table->string('text');
                $table->timestamps();
                $table->softDeletes();
            },
            'car_quote_request' =>
            function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->string('code')->nullable();
                $table->string('previous_quote_policy_number')->nullable();
                $table->date('previous_policy_expiry_date')->nullable();
                $table->string('source')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->nullable();
                $table->string('mobile_no')->nullable();
                $table->unsignedBigInteger('quote_status_id')->nullable();
                $table->unsignedBigInteger('advisor_id')->nullable();
                $table->boolean('is_ecommerce')->default(0);
                $table->decimal('car_value', 15, 2)->nullable();
                $table->unsignedBigInteger('car_make_id')->nullable();
                $table->unsignedBigInteger('car_model_id')->nullable();
                $table->unsignedBigInteger('nationality_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->date('policy_expiry_date')->nullable();
                $table->unsignedBigInteger('tier_id')->nullable();
                $table->unsignedBigInteger('quote_batch_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            },
            'renewals_upload_leads'=>
            function (Blueprint $table) {
                $table->id();
                $table->string('file_name');
                $table->string('file_path');
                $table->string('quote_type')->nullable();
                $table->string('status')->nullable();
                $table->string('renewal_import_type')->nullable();
                $table->boolean('is_sic')->default(0);
                $table->timestamps();
            },
            'renewal_quote_processes' =>
            function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('renewals_upload_lead_id');
                $table->unsignedBigInteger('quote_id');
                $table->json('data');
                $table->longText('validation_errors')->nullable();
                $table->longText('step_errors')->nullable();
                $table->string('status');
                $table->string('quote_type');
                $table->string('type');
                $table->string('fetch_plans_status')->nullable();
                $table->string('batch')->nullable();
                $table->string('policy_number')->nullable();
                $table->string('renewal_batch_id')->nullable();
                $table->string('step')->nullable();
                $table->string('retry_count')->nullable();
                $table->string('last_step_attempted')->nullable();
                $table->softDeletes();
                $table->timestamps();

            },
        ]);
    }
}