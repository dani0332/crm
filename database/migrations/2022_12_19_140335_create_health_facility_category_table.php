<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHealthFacilityCategoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('health_facility_category', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('text', 50)->nullable();
            $table->string('text_ar', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unique('code');
            $table->timestamps();
            $table->softDeletes('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('health_facility_category');
    }
}
