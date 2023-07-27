<?php

use Illuminate\Database\Migrations\Migration;

class AddApplicationStorageForBasmaPrice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('application_storage')->insert(
            [
                'key_name' => 'BASMA_PRICE',
                'value' => 70,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        );
    }
}
