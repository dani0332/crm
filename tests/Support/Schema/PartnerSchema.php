<?php

namespace Tests\Support\Schema;

use Illuminate\Database\Schema\Blueprint;

class PartnerSchema
{
    public function register(): void
    {
        SchemaUtils::ensureTables([
            'vehicle_type' => function (Blueprint $table) {
                $table->id();
                $table->string('text')->nullable();
                $table->string('text_ar')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();
                $table->softDeletes();
            },
            'insurance_partners' => function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('email')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            },
            'insurance_partner_providers' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('partner_id');
                $table->unsignedBigInteger('provider_id');
                $table->unsignedBigInteger('quote_type_id');
                $table->boolean('auto_issuance_enabled')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            },
            'insurance_partner_provider_plans' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('partner_provider_id');
                $table->unsignedBigInteger('plan_id');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            },
        ]);

        SchemaUtils::ensureColumns([
            'quote_tags' => [
                'value' => function (Blueprint $table) {
                    $table->string('value')->nullable();
                },
            ],
        ]);
    }
}
