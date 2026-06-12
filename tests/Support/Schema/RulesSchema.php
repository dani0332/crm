<?php

namespace Tests\Support\Schema;

use Illuminate\Database\Schema\Blueprint;

class RulesSchema
{
    public function register(): void
    {
        $this->ensureRulesAndRelatedTables();
    }

    private function ensureRulesAndRelatedTables(): void
    {
        SchemaUtils::ensureTables([
            'lead_sources' => function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('code')->nullable();
                $table->boolean('is_active')->default(1);
                $table->boolean('is_applicable_for_rules')->default(0);
                $table->timestamps();
            },
            'rule_types' => function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            },
            'rules' => function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->date('rule_start_date')->nullable();
                $table->date('rule_end_date')->nullable();
                $table->boolean('is_active')->default(1);
                $table->unsignedBigInteger('rule_type');
                $table->unsignedBigInteger('quote_type_id');
                $table->timestamps();
            },
            'rule_users' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rule_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();
            },
            'rule_details' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rule_id');
                $table->unsignedBigInteger('car_make_id')->nullable();
                $table->unsignedBigInteger('car_model_id')->nullable();
                $table->unsignedBigInteger('lead_source_id')->nullable();
                $table->string('utm_source')->nullable();
                $table->string('utm_campaign')->nullable();
                $table->string('utm_medium')->nullable();
                $table->timestamps();
            },
            'rule_lead_sources' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rule_id');
                $table->unsignedBigInteger('lead_source_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();
            },
        ]);
    }
}
