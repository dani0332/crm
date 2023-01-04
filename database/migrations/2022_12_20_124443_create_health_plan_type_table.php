<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHealthPlanTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('health_plan_type')) {
            Schema::create('health_plan_type', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50);
                $table->string('text', 50)->nullable();
                $table->string('text_ar', 50)->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->nullable();
                $table->unique('code');
                $table->softDeletes('deleted_at');
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
        Schema::dropIfExists('health_plan_type');
    }
}
