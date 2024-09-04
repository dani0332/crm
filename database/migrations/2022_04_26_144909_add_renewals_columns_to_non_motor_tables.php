<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRenewalsColumnsToNonMotorTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('business_quote_request', 'renewal_batch')) {
            Schema::table('business_quote_request', function (Blueprint $table) {
                $table->integer('renewal_batch')->nullable();
                $table->index('renewal_batch', 'idx_renewal_bacth_number');
            });
        }
        if (! Schema::hasColumn('business_quote_request', 'policy_expiry_date')) {
            Schema::table('business_quote_request', function (Blueprint $table) {
                $table->dateTime('policy_expiry_date')->nullable();
                $table->index('policy_expiry_date', 'idx_policy_expiry_date');
            });
        }
        if (! Schema::hasColumn('business_quote_request', 'previous_quote_policy_number')) {
            Schema::table('business_quote_request', function (Blueprint $table) {
                $table->string('previous_quote_policy_number', '100')->nullable();
                $table->index('previous_quote_policy_number', 'idx_previous_quote_policy_number');
            });
        }

        if (! Schema::hasColumn('health_quote_request', 'renewal_batch')) {
            Schema::table('health_quote_request', function (Blueprint $table) {
                $table->integer('renewal_batch')->nullable();
                $table->index('renewal_batch', 'idx_renewal_bacth_number');
            });
        }
        if (! Schema::hasColumn('health_quote_request', 'policy_expiry_date')) {
            Schema::table('health_quote_request', function (Blueprint $table) {
                $table->dateTime('policy_expiry_date')->nullable();
                $table->index('policy_expiry_date', 'idx_policy_expiry_date');
            });
        }
        if (! Schema::hasColumn('health_quote_request', 'previous_quote_policy_number')) {
            Schema::table('health_quote_request', function (Blueprint $table) {
                $table->string('previous_quote_policy_number', '100')->nullable();
                $table->index('previous_quote_policy_number', 'idx_previous_quote_policy_number');
            });
        }

        if (! Schema::hasColumn('travel_quote_request', 'renewal_batch')) {
            Schema::table('travel_quote_request', function (Blueprint $table) {
                $table->integer('renewal_batch')->nullable();
                $table->index('renewal_batch', 'idx_renewal_bacth_number');
            });
        }
        if (! Schema::hasColumn('travel_quote_request', 'policy_expiry_date')) {
            Schema::table('travel_quote_request', function (Blueprint $table) {
                $table->dateTime('policy_expiry_date')->nullable();
                $table->index('policy_expiry_date', 'idx_policy_expiry_date');
            });
        }
        if (! Schema::hasColumn('travel_quote_request', 'previous_quote_policy_number')) {
            Schema::table('travel_quote_request', function (Blueprint $table) {
                $table->string('previous_quote_policy_number', '100')->nullable();
                $table->index('previous_quote_policy_number', 'idx_previous_quote_policy_number');
            });
        }

        if (! Schema::hasColumn('home_quote_request', 'renewal_batch')) {
            Schema::table('home_quote_request', function (Blueprint $table) {
                $table->integer('renewal_batch')->nullable();
                $table->index('renewal_batch', 'idx_renewal_bacth_number');
            });
        }
        if (! Schema::hasColumn('home_quote_request', 'policy_expiry_date')) {
            Schema::table('home_quote_request', function (Blueprint $table) {
                $table->dateTime('policy_expiry_date')->nullable();
                $table->index('policy_expiry_date', 'idx_policy_expiry_date');
            });
        }
        if (! Schema::hasColumn('home_quote_request', 'previous_quote_policy_number')) {
            Schema::table('home_quote_request', function (Blueprint $table) {
                $table->string('previous_quote_policy_number', '100')->nullable();
                $table->index('previous_quote_policy_number', 'idx_previous_quote_policy_number');
            });
        }

        if (! Schema::hasColumn('life_quote_request', 'renewal_batch')) {
            Schema::table('life_quote_request', function (Blueprint $table) {
                $table->integer('renewal_batch')->nullable();
                $table->index('renewal_batch', 'idx_renewal_bacth_number');
            });
        }
        if (! Schema::hasColumn('life_quote_request', 'policy_expiry_date')) {
            Schema::table('life_quote_request', function (Blueprint $table) {
                $table->dateTime('policy_expiry_date')->nullable();
                $table->index('policy_expiry_date', 'idx_policy_expiry_date');
            });
        }
        if (! Schema::hasColumn('life_quote_request', 'previous_quote_policy_number')) {
            Schema::table('life_quote_request', function (Blueprint $table) {
                $table->string('previous_quote_policy_number', '100')->nullable();
                $table->index('previous_quote_policy_number', 'idx_previous_quote_policy_number');
            });
        }

        if (! Schema::hasColumn('bike_quote_request', 'renewal_batch')) {
            Schema::table('bike_quote_request', function (Blueprint $table) {
                $table->integer('renewal_batch')->nullable();
                $table->index('renewal_batch', 'idx_renewal_bacth_number');
            });
        }
        if (! Schema::hasColumn('bike_quote_request', 'policy_expiry_date')) {
            Schema::table('bike_quote_request', function (Blueprint $table) {
                $table->dateTime('policy_expiry_date')->nullable();
                $table->index('policy_expiry_date', 'idx_policy_expiry_date');
            });
        }
        if (! Schema::hasColumn('bike_quote_request', 'previous_quote_policy_number')) {
            Schema::table('bike_quote_request', function (Blueprint $table) {
                $table->string('previous_quote_policy_number', '100')->nullable();
                $table->index('previous_quote_policy_number', 'idx_previous_quote_policy_number');
            });
        }

        if (! Schema::hasColumn('yacht_quote_request', 'renewal_batch')) {
            Schema::table('yacht_quote_request', function (Blueprint $table) {
                $table->integer('renewal_batch')->nullable();
                $table->index('renewal_batch', 'idx_renewal_bacth_number');
            });
        }
        if (! Schema::hasColumn('yacht_quote_request', 'policy_expiry_date')) {
            Schema::table('yacht_quote_request', function (Blueprint $table) {
                $table->dateTime('policy_expiry_date')->nullable();
                $table->index('policy_expiry_date', 'idx_policy_expiry_date');
            });
        }
        if (! Schema::hasColumn('yacht_quote_request', 'previous_quote_policy_number')) {
            Schema::table('yacht_quote_request', function (Blueprint $table) {
                $table->string('previous_quote_policy_number', '100')->nullable();
                $table->index('previous_quote_policy_number', 'idx_previous_quote_policy_number');
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
        Schema::table('non_motor_tables', function (Blueprint $table) {
            //
        });
    }
}
