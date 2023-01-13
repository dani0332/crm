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
        if (! Schema::hasTable('health_facility_category')) {
            Schema::create('health_facility_category', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50);
                $table->string('text', 50)->nullable();
                $table->string('text_ar', 50)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unique('code');
                $table->softDeletes('deleted_at');
                $table->timestamps();
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
        Schema::dropIfExists('health_facility_category');
    }
}
