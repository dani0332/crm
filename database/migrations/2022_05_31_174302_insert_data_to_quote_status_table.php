<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertDataToQuoteStatusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quote_status', function (Blueprint $table) {
            if(!Schema::hasColumn('quote_status', 'is_renewal')) {
                $table->boolean('is_renewal')->default(false);
            }
        });

        $datetime = date('Y-m-d H:i:s');
        $pendingRenewal = DB::table('quote_status')->where('code', 'Pending_Renewal')->first();
        if ($pendingRenewal === null) {
            DB::table('quote_status')->insert(
                array(
                    'code' => 'Pending_Renewal',
                    'text' => 'Pending Renewal',
                    'is_active' => true,
                    'is_deleted' => false,
                    'is_renewal' => true,
                    'created_at' => $datetime,
                    'updated_at' => $datetime
                )
            );
        }

        $initialCallDone = DB::table('quote_status')->where('code', 'Initial_Call_Done')->first();
        if ($initialCallDone === null) {
            DB::table('quote_status')->insert(
                array(
                    'code' => 'Initial_Call_Done',
                    'text' => 'Initial Call Done',
                    'is_active' => true,
                    'is_deleted' => false,
                    'is_renewal' => true,
                    'created_at' => $datetime,
                    'updated_at' => $datetime
                )
            );
        }

        $renewedWithSameInsurer = DB::table('quote_status')->where('code', 'Renewed_With_Same_Insurer')->first();
        if ($renewedWithSameInsurer === null) {
            DB::table('quote_status')->insert(
                array(
                    'code' => 'Renewed_With_Same_Insurer',
                    'text' => 'Renewed with Same Insurer',
                    'is_active' => true,
                    'is_deleted' => false,
                    'is_renewal' => true,
                    'created_at' => $datetime,
                    'updated_at' => $datetime
                )
            );
        }

        $renewedWithDifferentInsurer = DB::table('quote_status')->where('code', 'Renewed_With_Different_Insurer')->first();
        if ($renewedWithDifferentInsurer === null) {
            DB::table('quote_status')->insert(
                array(
                    'code' => 'Renewed_With_Different_Insurer',
                    'text' => 'Renewed with Different Insurer',
                    'is_active' => true,
                    'is_deleted' => false,
                    'is_renewal' => true,
                    'created_at' => $datetime,
                    'updated_at' => $datetime
                )
            );
        }

        $carSold = DB::table('quote_status')->where('code', 'Car_Sold')->first();
        if ($carSold === null) {
            DB::table('quote_status')->insert(
                array(
                    'code' => 'Car_Sold',
                    'text' => 'Car Sold',
                    'is_active' => true,
                    'is_deleted' => false,
                    'is_renewal' => true,
                    'created_at' => $datetime,
                    'updated_at' => $datetime
                )
            );
        }

        $uncontactable = DB::table('quote_status')->where('code', 'Uncontactable')->first();
        if ($uncontactable === null) {
            DB::table('quote_status')->insert(
                array(
                    'code' => 'Uncontactable',
                    'text' => 'Uncontactable',
                    'is_active' => true,
                    'is_deleted' => false,
                    'is_renewal' => true,
                    'created_at' => $datetime,
                    'updated_at' => $datetime
                )
            );
        }

        $cancelledInTheSystem = DB::table('quote_status')->where('code', 'Cancelled_In_The_System')->first();
        if ($cancelledInTheSystem === null) {
            DB::table('quote_status')->insert(
                array(
                    'code' => 'Cancelled_In_The_System',
                    'text' => 'Cancelled in the System',
                    'is_active' => true,
                    'is_deleted' => false,
                    'is_renewal' => true,
                    'created_at' => $datetime,
                    'updated_at' => $datetime
                )
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quote_status', function (Blueprint $table) {
            //
        });
    }
}
