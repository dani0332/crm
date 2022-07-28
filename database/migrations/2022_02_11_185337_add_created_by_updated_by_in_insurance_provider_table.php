<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatedByUpdatedByInInsuranceProviderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('insurance_provider', function (Blueprint $table) {
            if (! Schema::hasColumn('insurance_provider', 'created_by')) {
                $table->string('created_by')->nullable();
            }
            if (! Schema::hasColumn('insurance_provider', 'updated_by')) {
                $table->string('updated_by')->nullable();
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
        Schema::table('insurance_provider', function (Blueprint $table) {
            //
        });
    }
}
