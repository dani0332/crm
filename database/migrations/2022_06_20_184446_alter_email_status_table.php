<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterEmailStatusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('email_status', function (Blueprint $table) {
            if (! Schema::hasColumn('email_status', 'email_subject')) {
                $table->string('email_subject', '150')->nullable();
            }
            if (! Schema::hasColumn('email_status', 'template_id')) {
                $table->integer('template_id')->nullable();
            }
            if (! Schema::hasColumn('email_status', 'customer_id')) {
                $table->integer('customer_id')->nullable();
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
        Schema::table('email_status', function (Blueprint $table) {
            //
        });
    }
}
