<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHealthFacilityPlanMappingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('health_facility_plan_mapping')) {
            Schema::create('health_facility_plan_mapping', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('health_facility_id');
                $table->foreign('health_facility_id')->references('id')->on('health_facility')->onDelete('no action');

                $table->unsignedBigInteger('health_plan_id');
                $table->foreign('health_plan_id')->references('id')->on('health_plan')->onDelete('no action');

                $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('health_facility_plan_mapping');
    }
}
