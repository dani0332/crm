<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRuleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('lead_allocation_rules')) {
            Schema::create('lead_allocation_rules', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255)->nullable(false);
                $table->string('property_name', 255)->nullable(false);
                $table->integer('priority')->nullable(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('allocation_rules_properties')) {
            Schema::create('allocation_rules_properties', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255)->nullable(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('allocation_rule_users')) {
            Schema::create('allocation_rule_users', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('rule_id')->nullable();
                $table->foreign('rule_id')->references('id')->on('lead_allocation_rules')->onDelete('no action');
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
