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
                $table->smallInteger('total_payments')->unsigned()->nullable();
            }

            if (! Schema::hasColumn('payments', 'credit_approval')) {
                $table->string('credit_approval', 128)->nullable();
            }

            if (! Schema::hasColumn('payments', 'frequency')) {
                $table->string('frequency', 30)->nullable();
            }

            if (! Schema::hasColumn('payments', 'discount_type')) {
                $table->string('discount_type', 128)->nullable();
            }

            if (! Schema::hasColumn('payments', 'discount_reason')) {
                $table->string('discount_reason', 256)->nullable();
            }

            if (! Schema::hasColumn('payments', 'custom_reason')) {
                $table->text('custom_reason')->nullable();
            }

            if (! Schema::hasColumn('payments', 'notes')) {
                $table->text('notes')->nullable();
            }

            if (! Schema::hasColumn('payments', 'total_price')) {
                $table->float('total_price', 16, 2)->nullable();
            }

            if (! Schema::hasColumn('payments', 'collection_date')) {
                $table->dateTime('collection_date')->nullable();
            }

            if (! Schema::hasColumn('payments', 'discount_value')) {
                $table->float('discount_value', 16, 2)->nullable();
            }

            if (! Schema::hasColumn('payments', 'total_amount')) {
                $table->float('total_amount', 16, 2)->nullable();
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
