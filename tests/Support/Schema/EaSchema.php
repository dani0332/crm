<?php

namespace Tests\Support\Schema;

use Illuminate\Database\Schema\Blueprint;

class EaSchema
{
    public function register(): void
    {
        $this->ensureEaTables();
        $this->ensureEaColumns();
    }

    private function ensureEaTables(): void
    {
        SchemaUtils::ensureTables([
            'travel_quote_request_detail' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('travel_quote_request_id')->nullable();
                $table->timestamps();
            },
            'business_quote_request_detail' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_quote_request_id')->nullable();
                $table->timestamps();
            },
        ]);
    }

    private function ensureEaColumns(): void
    {
        $eaColumns = [
            'ea_model' => fn (Blueprint $t) => $t->string('ea_model')->nullable(),
            'lead_generator_id' => fn (Blueprint $t) => $t->unsignedBigInteger('lead_generator_id')->nullable(),
            'expert_advisor_id' => fn (Blueprint $t) => $t->unsignedBigInteger('expert_advisor_id')->nullable(),
            'ea_manager_id' => fn (Blueprint $t) => $t->unsignedBigInteger('ea_manager_id')->nullable(),
            'ea_manager_approved_at' => fn (Blueprint $t) => $t->timestamp('ea_manager_approved_at')->nullable(),
            'ea_manager_rejected_at' => fn (Blueprint $t) => $t->timestamp('ea_manager_rejected_at')->nullable(),
            'ea_assigned_advisor_approved_at' => fn (Blueprint $t) => $t->timestamp('ea_assigned_advisor_approved_at')->nullable(),
            'ea_expert_advisor_approved_at' => fn (Blueprint $t) => $t->timestamp('ea_expert_advisor_approved_at')->nullable(),
            'ea_assigned_advisor_rejected_at' => fn (Blueprint $t) => $t->timestamp('ea_assigned_advisor_rejected_at')->nullable(),
            'ea_expert_advisor_rejected_at' => fn (Blueprint $t) => $t->timestamp('ea_expert_advisor_rejected_at')->nullable(),
        ];

        SchemaUtils::ensureColumns([
            'personal_quotes' => $eaColumns,
            'car_quote_request' => $eaColumns,
            'health_quote_request' => $eaColumns,
            'travel_quote_request' => [
                ...$eaColumns,
                'email' => fn (Blueprint $t) => $t->string('email')->nullable(),
                'mobile_no' => fn (Blueprint $t) => $t->string('mobile_no')->nullable(),
            ],
            'business_quote_request' => [
                ...$eaColumns,
                'quote_status_date' => fn (Blueprint $t) => $t->timestamp('quote_status_date')->nullable(),
                'stale_at' => fn (Blueprint $t) => $t->timestamp('stale_at')->nullable(),
            ],
        ]);
    }
}
