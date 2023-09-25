<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRuleDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('rule_details')) {
            Schema::create('rule_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rule_id')
                    ->constrained('rules')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();

                $table->integer('car_make_id')->nullable();
                $table->foreign('car_make_id')
                    ->references('id')
                    ->on('car_make')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();

                $table->integer('car_model_id')->nullable();
                $table->foreign('car_model_id')
                    ->references('id')
                    ->on('car_model')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();

                $table->foreignId('lead_source_id')->nullable()
                    ->constrained('lead_sources')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();

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
        Schema::dropIfExists('rule_details');
    }
}
