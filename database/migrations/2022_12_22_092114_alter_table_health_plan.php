<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableHealthPlan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('health_plan')) {
            Schema::table('health_plan', function (Blueprint $table) {
                if (! Schema::hasColumn('health_plan', 'health_business_type')) {
                    $table->enum('health_business_type', ['EBP', 'RM'])->default('EBP');
                }
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
