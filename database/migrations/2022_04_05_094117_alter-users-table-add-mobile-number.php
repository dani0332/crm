<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterUsersTableAddMobileNumber extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('users', 'mobile_no') && ! Schema::hasColumn('users', 'landline_no')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('mobile_no', '20')->nullable();
                $table->string('landline_no', '20')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('users', 'mobile_no') && Schema::hasColumn('users', 'landline_no')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['mobile_no', 'landline_no']);
            });
        }
    }
}
