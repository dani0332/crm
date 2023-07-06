<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableTmLeadContactInformation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tm_lead_contact_information', function (Blueprint $table) {
            if (Schema::hasColumn('tm_lead_contact_information', 'email_address')) {
                $table->string('email_address')->nullable()->change();
            }
            if (Schema::hasColumn('tm_lead_contact_information', 'phone_number')) {
                $table->string('phone_number')->nullable()->change();
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
        Schema::table('tm_lead_contact_information', function (Blueprint $table) {
            if (Schema::hasColumn('tm_lead_contact_information', 'email_address')) {
                $table->string('email_address')->nullable(false)->change();
            }
            if (Schema::hasColumn('tm_lead_contact_information', 'phone_number')) {
                $table->string('phone_number')->nullable(false)->change();
            }
        });
    }
}
