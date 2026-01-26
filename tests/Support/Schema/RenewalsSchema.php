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
                $table->string('file_path');
                $table->string('quote_type')->nullable();
                $table->string('status')->nullable();
                $table->string('renewal_import_type')->nullable();
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
