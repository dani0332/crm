<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePersonalQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('personal_quotes', function (Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger('personal_quote_type_id')->nullable(false);
            $table->foreign('personal_quote_type_id')->references('id')->on('personal_quote_types');

            $table->string('uuid', 100)->unique()->nullable(false);
            $table->string('code', 50)->nullable(false);

            $table->string('company_to_insure', 100)->nullable();

            $table->string('first_name', 255)->nullable();
            $table->string('last_name', 255)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('mobile_no', 20)->nullable();
            $table->string('source', 255)->nullable();


            $table->decimal('value', 14, 2)->nullable();
            $table->integer('year_of_manufacture_id')->nullable();
            $table->foreign('year_of_manufacture_id')->references('id')->on('year_of_manufacture');

            $table->integer('insurance_provider_id')->nullable()->comment('currently insured with');
            $table->foreign('insurance_provider_id')->references('id')->on('insurance_provider');

            $table->bigInteger('customer_id')->nullable();
            $table->foreign('customer_id')->references('id')->on('customer')->onDelete('no action');

            //$table->integer('nationality_id')->nullable();
            //$table->foreign('nationality_id')->references('id')->on('nationality');

            //$table->integer('uae_license_held_for_id')->nullable();
            //$table->foreign('uae_license_held_for_id')->references('id')->on('uae_license_held_for')->onDelete('no action');

            $table->integer('payment_status_id')->nullable();
            $table->foreign('payment_status_id')->references('id')->on('payment_status');

            $table->integer('quote_status_id')->nullable();
            $table->foreign('quote_status_id')->references('id')->on('quote_status');

            $table->tinyInteger('is_synced')->nullable();
            $table->string('additional_notes', 1000)->nullable();

            $table->tinyInteger('is_ecommerce')->default(0);

            $table->string('device', 100)->nullable();
            $table->string('reference_url', 1000)->nullable();
            $table->string('reviver_name', 255)->nullable();
            $table->string('promo_code', 50)->nullable();

            $table->string('policy_number')->nullable();
            $table->unsignedBigInteger('previous_quote_id')->nullable();

            $table->unsignedBigInteger('advisor_id')->nullable();
            $table->foreign('advisor_id')->references('id')->on('users')->onDelete('no action');
            $table->unsignedBigInteger('previous_advisor_id')->nullable();
            $table->foreign('previous_advisor_id')->references('id')->on('users')->onDelete('no action');

            $table->unsignedBigInteger('pa_id')->nullable();

            $table->decimal('premium', 10,2)->nullable();
            $table->string('renewal_batch')->nullable();

            $table->dateTime('renewal_expiry_date')->nullable();
            $table->string('previous_quote_policy_number', 100)->nullable();
            $table->string('other_email_addresses', 500)->nullable();
            $table->string('renewal_import_code', 50)->nullable();
            $table->date('previous_policy_expiry_date')->nullable();
            $table->decimal('previous_quote_policy_premium', 10,2)->nullable();
            $table->dateTime('policy_start_date')->nullable();
            $table->dateTime('policy_issuance_date')->nullable();

            $table->json('data')->nullable();

            $table->timestamps();
        });

        Schema::table('bike_quote_request', function (Blueprint $table) {
            if (! Schema::hasColumn('bike_quote_request', 'personal_quote_id')) {
                $table->unsignedBigInteger('personal_quote_id')->nullable();
                //$table->foreign('personal_quote_id')->references('id')->on('personal_quotes');
            }
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('personal_quotes');
    }
}
