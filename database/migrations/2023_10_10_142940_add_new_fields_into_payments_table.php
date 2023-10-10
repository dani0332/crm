<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewFieldsInToPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('payments', function (Blueprint $table) {
           
            if (! Schema::hasColumn('payments', 'total_payments')) {
                $table->string('total_payments', 30)->nullable();
            }

            if (! Schema::hasColumn('payments', 'credit_approval')) {
                $table->string('credit_approval', 30)->nullable();
            }

            if (! Schema::hasColumn('payments', 'frequency')) {
                $table->string('frequency', 30)->nullable();
            }

            if (! Schema::hasColumn('payments', 'discount')) {
                $table->string('discount', 30)->nullable();
            }

            if (! Schema::hasColumn('payments', 'discount_reason')) {
                $table->string('discount_reason', 30)->nullable();
            }

            if (! Schema::hasColumn('payments', 'custom_reason')) {
                $table->text('custom_reason')->nullable();
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
        //
    }
}

