<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProfileColumnsToCustomer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer', function (Blueprint $table) {
            if (! Schema::hasColumn('customer', 'insured_first_name')) {
                $table->string('insured_first_name')->nullable();
            }

            if (! Schema::hasColumn('customer', 'insured_last_name')) {
                $table->string('insured_last_name')->nullable();
            }

            if (! Schema::hasColumn('customer', 'emirates_id_number')) {
                $table->string('emirates_id_number')->nullable();
            }

            if (! Schema::hasColumn('customer', 'emirates_id_expiry_date')) {
                $table->date('emirates_id_expiry_date')->nullable();
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
        Schema::table('customer', function (Blueprint $table) {
            //
        });
    }
}
