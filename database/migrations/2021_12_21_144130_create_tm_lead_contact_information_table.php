<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\BaseMigration;
class CreateTmLeadContactInformationTable extends BaseMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('tm_lead_contact_information')) {
            
            Schema::create('tm_lead_contact_information', function (Blueprint $table) {
                $table->id()->autoIncrement();
                $table->unsignedBigInteger('tm_lead_id');
                $table->index('tm_lead_id');
                $table->foreign('tm_lead_id')->references('id')->on('tm_leads');
                $table->string('email_address', '50')->nullable();
                $table->string('phone_number', '20')->nullable();
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
        Schema::dropIfExists('tm_lead_contact_information');
    }
}
