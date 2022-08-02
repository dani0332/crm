<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertPermissionCqpcToPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $datetime = date('Y-m-d H:i:s');
        $carQuotesPlansCreate = DB::table('permissions')->where('name', 'car-quotes-plans-create')->first();
        if ($carQuotesPlansCreate === null) {
            DB::table('permissions')->insert(
                [
                    'name' => 'car-quotes-plans-create',
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
