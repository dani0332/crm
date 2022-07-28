<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPolicyTypeToCarQuotePolicyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_quote_policy', function (Blueprint $table) {
            if (! Schema::hasColumn('car_quote_policy', 'policy_type')) {
                $table->string('policy_type', 200)->nullable();
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
        Schema::table('car_quote_policy', function (Blueprint $table) {
            //
        });
    }
}
