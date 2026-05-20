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
                $table->timestamps();
            },
            'renewal_quote_processes' => function (Blueprint $table) {
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
            ], $commonRenewalColumns),
        ]);
    }
}
