<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTierTableForLeadAllocation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('quadrants')) {
            Schema::create('quadrants', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255)->nullable(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('tiers')) {
            Schema::create('tiers', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255)->nullable(false);
                $table->float('min_price')->nullable(false);
                $table->float('max_price')->nullable(false);
                $table->float('cost_per_lead')->nullable();
                $table->boolean('is_tpl')->default(false);
                $table->boolean('is_auto_assignment_enabled')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('quad_tiers')) {
            Schema::create('quad_tiers', function (Blueprint $table) {
                $table->unsignedBigInteger('tier_id')->nullable();
                $table->unsignedBigInteger('quad_id')->nullable();
                $table->foreign('quad_id')->references('id')->on('quadrants')->onDelete('no action');
                $table->foreign('tier_id')->references('id')->on('tiers')->onDelete('no action');
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('quad_users')) {
            Schema::create('quad_users', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('quad_id')->nullable();
                $table->foreign('quad_id')->references('id')->on('quadrants')->onDelete('no action');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('no action');
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('tier_users')) {
            Schema::create('tier_users', function (Blueprint $table) {
                $table->unsignedBigInteger('tier_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->foreign('tier_id')->references('id')->on('tiers')->onDelete('no action');
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
