<?php

use App\Models\BaseMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarQuoteEmailUniqueLinkTable extends BaseMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('car_quote_email_unique_link')) {
            Schema::create('car_quote_email_unique_link', function (Blueprint $table) {
                $table->id()->autoIncrement();
                $table->bigInteger('car_quote_id');
                $table->string('hash');
                $table->string('status')->nullable();
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
        Schema::dropIfExists('car_quote_email_unique_link');
    }
}
