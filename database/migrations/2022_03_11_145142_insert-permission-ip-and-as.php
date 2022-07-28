<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InsertPermissionIpAndAs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $datetime = date('Y-m-d H:i:s');
        $insuranceProviderList = DB::table('permissions')->where('name', 'inusrance-provider-list')->first();
        if ($insuranceProviderList === null) {
            DB::table('permissions')->insert(
                [
                    'name' => 'inusrance-provider-list',
                    'guard_name' => 'web',
                    'created_at' => $datetime,
                    'updated_at' => $datetime,
                ]
            );
        }
        $insuranceProviderCreate = DB::table('permissions')->where('name', 'inusrance-provider-create')->first();
        if ($insuranceProviderCreate === null) {
            DB::table('permissions')->insert(
                [
                    'name' => 'inusrance-provider-create',
                    'guard_name' => 'web',
                    'created_at' => $datetime,
                    'updated_at' => $datetime,
                ]
            );
        }
        $insuranceProviderEdit = DB::table('permissions')->where('name', 'inusrance-provider-edit')->first();
        if ($insuranceProviderEdit === null) {
            DB::table('permissions')->insert(
                [
                    'name' => 'inusrance-provider-edit',
                    'guard_name' => 'web',
                    'created_at' => $datetime,
                    'updated_at' => $datetime,
                ]
            );
        }
        $appStorageList = DB::table('permissions')->where('name', 'application-storage-list')->first();
        if ($appStorageList === null) {
            DB::table('permissions')->insert(
                [
                    'name' => 'application-storage-list',
                    'guard_name' => 'web',
                    'created_at' => $datetime,
                    'updated_at' => $datetime,
                ]
            );
        }
        $appStorageEdit = DB::table('permissions')->where('name', 'application-storage-edit')->first();
        if ($appStorageEdit === null) {
            DB::table('permissions')->insert(
                [
                    'name' => 'application-storage-edit',
                    'guard_name' => 'web',
                    'created_at' => $datetime,
                    'updated_at' => $datetime,
                ]
            );
        }
        $appStorageCreate = DB::table('permissions')->where('name', 'application-storage-create')->first();
        if ($appStorageCreate === null) {
            DB::table('permissions')->insert(
                [
                    'name' => 'application-storage-create',
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
