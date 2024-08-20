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
        if (! Schema::hasTable('personal_quotes')) {
            Schema::create('personal_quotes', function (Blueprint $table) {
                $table->id();
                $table->integer('quote_type_id')->nullable(false);
                $table->foreign('quote_type_id')->references('id')->on('quote_type');

                $table->string('uuid', 15)->unique()->nullable(false);
                $table->string('code', 15)->nullable(false);
                $table->string('first_name', 255)->nullable();
                $table->string('last_name', 255)->nullable();
                $table->date('dob')->nullable();
                $table->integer('nationality_id')->nullable();
                $table->foreign('nationality_id')->references('id')->on('nationality');

                $table->string('email', 100)->nullable();
                $table->string('mobile_no', 20)->nullable();
                $table->string('source', 255)->nullable();

                $table->decimal('asset_value', 14, 2)->nullable();
                $table->integer('currently_insured_with_id')->nullable();
                $table->foreign('currently_insured_with_id')->references('id')->on('insurance_provider');

                $table->bigInteger('customer_id')->nullable();
                $table->foreign('customer_id')->references('id')->on('customer')->onDelete('no action');

                $table->string('device', 100)->nullable();
                $table->string('reference_url', 1000)->nullable();
                $table->string('policy_number')->nullable();
                $table->unsignedBigInteger('advisor_id')->nullable();
                $table->foreign('advisor_id')->references('id')->on('users')->onDelete('no action');
                $table->decimal('premium', 10, 2)->nullable();
                $table->string('renewal_batch', 50)->nullable();
                $table->dateTime('policy_expiry_date')->nullable();
                $table->string('previous_quote_policy_number', 100)->nullable();
                $table->string('renewal_import_code', 50)->nullable();
                $table->date('previous_policy_expiry_date')->nullable();
                $table->decimal('previous_quote_policy_premium', 10, 2)->nullable();
                $table->dateTime('policy_start_date')->nullable();
                $table->dateTime('policy_issuance_date')->nullable();

                $table->unsignedBigInteger('plan_id')->nullable();
                $table->dateTime('paid_at')->nullable();

                $table->decimal('premium_authorized', 10, 2)->nullable();
                $table->decimal('premium_captured', 10, 2)->nullable();
                $table->decimal('premium_refunded', 10, 2)->nullable();

                $table->integer('payment_status_id')->nullable();
                $table->foreign('payment_status_id')->references('id')->on('payment_status');
                $table->dateTime('payment_status_date')->nullable();

                $table->integer('quote_status_id')->nullable();
                $table->foreign('quote_status_id')->references('id')->on('quote_status');
                $table->dateTime('quote_status_date')->nullable();

                $table->string('notes', 500)->nullable();
                $table->boolean('is_ecommerce')->default(0);

                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->foreign('created_by_id')->references('id')->on('users')->onDelete('no action');

                $table->unsignedBigInteger('updated_by_id')->nullable();
                $table->foreign('updated_by_id')->references('id')->on('users');

                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
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
        Schema::dropIfExists('personal_quotes');
    }
}
