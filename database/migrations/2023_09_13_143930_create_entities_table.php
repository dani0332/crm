<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEntitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('entities')) {
            Schema::create('entities', function (Blueprint $table) {
                $table->id();
                $table->string('code');
                $table->string('trade_license_no');
                $table->string('company_name');
                $table->string('company_address');
                $table->string('industry_type_code')->nullable();
                $table->integer('emirate_of_registration_id')->nullable();
                $table->foreign('emirate_of_registration_id')->references('id')->on('emirates');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
}
