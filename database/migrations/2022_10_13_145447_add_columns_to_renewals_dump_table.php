<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToRenewalsDumpTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renewals_dump', function (Blueprint $table) {
            if (! Schema::hasColumn('renewals_dump', 'uploaded_data')) {
                $table->json('uploaded_data')->nullable()->after('data');
            }
        });

        Schema::table('renewals_dump', function (Blueprint $table) {
            if (! Schema::hasColumn('renewals_dump', 'batch')) {
                $table->unsignedBigInteger('batch')->nullable()->after('uploaded_data');
            }
        });

        Schema::table('renewals_dump', function (Blueprint $table) {
            if (! Schema::hasColumn('renewals_dump', 'email_sent')) {
                $table->unsignedTinyInteger('email_sent')->default(0)->after('batch');
            }
        });

        Schema::table('renewals_dump', function (Blueprint $table) {
            if (! Schema::hasColumn('renewals_dump', 'validation_errors')) {
                $table->json('validation_errors')->nullable()->after('email_sent');
            }
        });

        Schema::table('renewals_dump', function (Blueprint $table) {
            if (! Schema::hasColumn('renewals_dump', 'plans_fetched')) {
                $table->boolean('plans_fetched')->default(false)->after('validation_errors');
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
        Schema::table('renewals_dump', function (Blueprint $table) {
            if (Schema::hasColumn('renewals_dump', 'uploaded_data')) {
                $table->dropColumn('uploaded_data');
            }
        });

        Schema::table('renewals_dump', function (Blueprint $table) {
            if (Schema::hasColumn('renewals_dump', 'batch')) {
                $table->dropColumn('batch');
            }
        });

        Schema::table('renewals_dump', function (Blueprint $table) {
            if (Schema::hasColumn('renewals_dump', 'email_sent')) {
                $table->dropColumn('email_sent');
            }
        });

        Schema::table('renewals_dump', function (Blueprint $table) {
            if (Schema::hasColumn('renewals_dump', 'validation_errors')) {
                $table->dropColumn('validation_errors');
            }
        });

        Schema::table('renewals_dump', function (Blueprint $table) {
            if (Schema::hasColumn('renewals_dump', 'plans_fetched')) {
                $table->dropColumn('plans_fetched');
            }
        });
    }
}
