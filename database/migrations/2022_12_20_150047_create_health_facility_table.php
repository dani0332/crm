<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHealthFacilityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('health_facility')) {
            Schema::create('health_facility', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50);

                $table->unsignedBigInteger('health_facility_category_id');
                $table->foreign('health_facility_category_id')->references('id')->on('health_facility_category')->onDelete('no action');

                $table->integer('emirates_id');
                $table->foreign('emirates_id')->references('id')->on('emirates')->onDelete('no action');

                $table->string('street', 50)->nullable();
                $table->string('address')->nullable();
                $table->decimal('lat', 11, 8)->nullable();
                $table->decimal('lng', 12, 8)->nullable();
                $table->string('google_plus_code', 100)->nullable();
                $table->string('google_maps_link', 500)->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('website')->nullable();
                $table->string('text', 100)->nullable();
                $table->string('text_ar', 100)->nullable();
                $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('health_facility');
    }
}
