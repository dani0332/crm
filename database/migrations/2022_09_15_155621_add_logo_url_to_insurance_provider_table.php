<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLogoUrlToInsuranceProviderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('insurance_provider', function (Blueprint $table) {
            if (Schema::hasTable('insurance_provider') && ! Schema::hasColumn('insurance_provider', 'logo_url')) {
                $table->string('logo_url', 255)->nullable();
            }
        });
    }
}
