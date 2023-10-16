<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnEpCertificates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('embedded_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('embedded_transactions', 'certificate_number')) {
                $table->string('certificate_number', 20)->nullable()->index();
            }
        });

        Schema::table('embedded_products', function (Blueprint $table) {
            if (! Schema::hasColumn('embedded_products', 'certificate_number_counter')) {
                $table->integer('certificate_number_counter')->nullable();
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
