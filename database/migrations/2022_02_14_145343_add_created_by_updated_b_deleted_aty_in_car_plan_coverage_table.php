<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatedByUpdatedBDeletedAtyInCarPlanCoverageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_plan_coverage', function (Blueprint $table) {
            if (! Schema::hasColumn('car_plan_coverage', 'created_by')) {
                $table->string('created_by')->nullable();
            }
            if (! Schema::hasColumn('car_plan_coverage', 'updated_by')) {
                $table->string('updated_by')->nullable();
            }
            if (! Schema::hasColumn('car_plan_coverage', 'deleted_at')) {
                $table->dateTime('deleted_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('car_plan_coverage', function (Blueprint $table) {
            //
        });
    }
}
