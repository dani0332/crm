<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRulesRelatedTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('lead_sources')) {
            Schema::create('lead_sources', function (Blueprint $table) {
                $table->id();
                $table->string('name', 500)->nullable(false);
                $table->string('code', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('rules')) {
            Schema::create('rules', function (Blueprint $table) {
                $table->id();
                $table->string('name', 250)->nullable(false);
                $table->dateTime('rule_start_date')->nullable();
                $table->dateTime('rule_end_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('rule_lead_sources')) {
            Schema::create('rule_lead_sources', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rule_id')->nullable();
                $table->foreign('rule_id')->references('id')->on('rules')->onDelete('no action');

                $table->unsignedBigInteger('lead_source_id')->nullable();
                $table->foreign('lead_source_id')->references('id')->on('lead_sources')->onDelete('no action');

                $table->unsignedBigInteger('user_id')->nullable();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('no action');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
