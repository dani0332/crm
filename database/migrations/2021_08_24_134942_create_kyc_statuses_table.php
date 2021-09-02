<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\BaseMigration;
class CreateKycStatusesTable extends BaseMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('kyc_statuses')) {
            Schema::create('kyc_statuses', function (Blueprint $table) {
                $table->id()->autoIncrement();
                $table->string('code')->nullable();
                $table->string('text');
                $table->string('text_ar')->nullable();;
                parent::commonFields($table);
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
        Schema::dropIfExists('kyc_statuses');
    }
}
