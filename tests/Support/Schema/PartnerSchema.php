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
            'partners' => function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('email')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            },
            'partner_plans' => function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('partner_id');
                $table->unsignedBigInteger('provider_id');
                $table->timestamps();
            },
        ]);
    }
}
