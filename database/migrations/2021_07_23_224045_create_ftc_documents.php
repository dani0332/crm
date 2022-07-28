<?php

use App\Models\BaseMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFtcDocuments extends BaseMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('car_quote_ftc_documents')) {
            Schema::create('car_quote_ftc_documents', function (Blueprint $table) {
                $table->id()->autoIncrement();
                $table->string('file_name', '255')->nullable();
                $table->integer('document');

                $table->bigInteger('car_quote_id');
                $table->foreign('car_quote_id')->references('id')->on('car_quote_request');

                $table->timestamps();
                $table->softDeletes();
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
        Schema::dropIfExists('ftc_documents');
    }
}
