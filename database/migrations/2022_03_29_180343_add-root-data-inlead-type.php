<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRootDataInleadType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $datetime = date('Y-m-d H:i:s');
        $individual = DB::table('health_lead_type')->where('text', 'Individual')->first();
        if ($individual === null) {
            DB::table('health_lead_type')->insert(
                [
                    'text' => 'Individual',
                    'is_active' => true,
                    'is_deleted' => false,
                    'created_at' => $datetime,
                    'updated_at' => $datetime,
                ]
            );
        }
        $SME = DB::table('health_lead_type')->where('text', 'SME')->first();
        if ($SME === null) {
            DB::table('health_lead_type')->insert(
                [
                    'text' => 'SME',
                    'is_active' => true,
                    'is_deleted' => false,
                    'created_at' => $datetime,
                    'updated_at' => $datetime,
                ]
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
        Schema::table('health_lead_type', function (Blueprint $table) {
            //
        });
    }
}
