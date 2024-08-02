<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPolicyFeeToInsuranceProviderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('insurance_provider', function (Blueprint $table) {
            if (! Schema::hasColumn('insurance_provider', 'policy_fee')) {
                $table->decimal('policy_fee', 10, 2)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
}
