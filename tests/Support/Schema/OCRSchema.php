<?php

declare(strict_types=1);

namespace Tests\Support\Schema;

use Illuminate\Database\Schema\Blueprint;

class OCRSchema
{
    public function register(): void
    {
        $this->ensureTables();
        $this->ensureColumns();
    }

    private function ensureTables(): void
    {
        SchemaUtils::ensureTables([
            'ocr_logs' => function (Blueprint $table) {
                $table->id();
                $table->morphs('ocr_loggable'); // ocr_loggable_type, ocr_loggable_id
                $table->string('document_type_code')->nullable();
                $table->string('document_type_name')->nullable();
                $table->string('status')->nullable();
                $table->json('request_data')->nullable();
                $table->json('response_data')->nullable();
                $table->decimal('execution_time_ms', 15, 2)->nullable();
                $table->text('error_message')->nullable();
                $table->unsignedBigInteger('provider_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('uploaded_through')->nullable();
                $table->timestamps();
            },

            'passport_visa_details' => function (Blueprint $table) {
                $table->id();
                $table->string('passport_number')->nullable();
                $table->string('name')->nullable();
                $table->string('visa_number')->nullable();
                $table->string('visa_type')->nullable();
                $table->date('visa_issue_date')->nullable();
                $table->date('visa_expiry_date')->nullable();
                $table->string('visa_issuance_authority')->nullable();
                $table->string('profession')->nullable();
                $table->string('sponsor')->nullable();
                $table->morphs('quoteable'); // quoteable_type, quoteable_id
                $table->unsignedBigInteger('customer_member_id')->nullable();
                $table->timestamps();
                $table->string('visa_file_number')->nullable();
            },
        ]);
    }

    private function ensureColumns(): void
    {
        SchemaUtils::addColumnIfMissing('document_types', 'category', function (Blueprint $table) {
            $table->string('category')->nullable();
        });
    }
}
