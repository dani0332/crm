<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatedUpdatedFieldsToFtcHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ftc_history', function (Blueprint $table) {
            if(!Schema::hasColumn('ftc_history','created_by'))
                $table->string('created_by')->nullable();
            if(!Schema::hasColumn('ftc_history','updated_by'))
                $table->string('updated_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ftc_history', function (Blueprint $table) {
            //
        });
    }
}
