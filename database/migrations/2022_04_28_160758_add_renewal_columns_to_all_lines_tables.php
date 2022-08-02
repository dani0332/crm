<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRenewalColumnsToAllLinesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('car_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('car_quote_request', 'other_email_addresses')) {
                $table->string('other_email_addresses', '355')->nullable();
                $table->index('other_email_addresses', 'idx_other_email_addresses');
            }
            if (! Schema::hasColumn('car_quote_request', 'renewal_import_code')) {
                $table->string('renewal_import_code', '20')->nullable();
                $table->index('renewal_import_code', 'idx_renewal_import_code');
            }
        });

        Schema::table('health_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('health_quote_request', 'other_email_addresses')) {
                $table->string('other_email_addresses', '355')->nullable();
                $table->index('other_email_addresses', 'idx_other_email_addresses');
            }
            if (! Schema::hasColumn('health_quote_request', 'renewal_import_code')) {
                $table->string('renewal_import_code', '20')->nullable();
                $table->index('renewal_import_code', 'idx_renewal_import_code');
            }
        });

        Schema::table('travel_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('travel_quote_request', 'other_email_addresses')) {
                $table->string('other_email_addresses', '355')->nullable();
                $table->index('other_email_addresses', 'idx_other_email_addresses');
            }
            if (! Schema::hasColumn('travel_quote_request', 'renewal_import_code')) {
                $table->string('renewal_import_code', '20')->nullable();
                $table->index('renewal_import_code', 'idx_renewal_import_code');
            }
        });

        Schema::table('life_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('life_quote_request', 'other_email_addresses')) {
                $table->string('other_email_addresses', '355')->nullable();
                $table->index('other_email_addresses', 'idx_other_email_addresses');
            }
            if (! Schema::hasColumn('life_quote_request', 'renewal_import_code')) {
                $table->string('renewal_import_code', '20')->nullable();
                $table->index('renewal_import_code', 'idx_renewal_import_code');
            }
        });

        Schema::table('home_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('home_quote_request', 'other_email_addresses')) {
                $table->string('other_email_addresses', '355')->nullable();
                $table->index('other_email_addresses', 'idx_other_email_addresses');
            }
            if (! Schema::hasColumn('home_quote_request', 'renewal_import_code')) {
                $table->string('renewal_import_code', '20')->nullable();
                $table->index('renewal_import_code', 'idx_renewal_import_code');
            }
        });

        Schema::table('yacht_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('yacht_quote_request', 'other_email_addresses')) {
                $table->string('other_email_addresses', '355')->nullable();
                $table->index('other_email_addresses', 'idx_other_email_addresses');
            }
            if (! Schema::hasColumn('yacht_quote_request', 'renewal_import_code')) {
                $table->string('renewal_import_code', '20')->nullable();
                $table->index('renewal_import_code', 'idx_renewal_import_code');
            }
        });

        Schema::table('bike_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('bike_quote_request', 'other_email_addresses')) {
                $table->string('other_email_addresses', '355')->nullable();
                $table->index('other_email_addresses', 'idx_other_email_addresses');
            }
            if (! Schema::hasColumn('bike_quote_request', 'renewal_import_code')) {
                $table->string('renewal_import_code', '20')->nullable();
                $table->index('renewal_import_code', 'idx_renewal_import_code');
            }
        });

        Schema::table('business_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('business_quote_request', 'other_email_addresses')) {
                $table->text('other_email_addresses')->nullable();
                $table->index('other_email_addresses', 'idx_other_email_addresses');
            }
            if (! Schema::hasColumn('business_quote_request', 'renewal_import_code')) {
                $table->string('renewal_import_code', '20')->nullable();
                $table->index('renewal_import_code', 'idx_renewal_import_code');
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
        Schema::table('all_lines_tables', function (Blueprint $table) {
            //
        });
    }
}
