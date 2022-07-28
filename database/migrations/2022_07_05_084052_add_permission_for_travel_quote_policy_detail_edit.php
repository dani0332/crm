<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPermissionForTravelQuotePolicyDetailEdit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $datetime = date('Y-m-d H:i:s');
        $travelQuotePolicyDetail = DB::table('permissions')->where('name', 'travel-quotes-policy-detail-edit')->first();
        if ($travelQuotePolicyDetail === null) {
            DB::table('permissions')->insert(
                [
                    'name' => 'travel-quotes-policy-detail-edit',
                    'guard_name' => 'web',
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
        Schema::table('permissions', function (Blueprint $table) {
            //
        });
    }
}
