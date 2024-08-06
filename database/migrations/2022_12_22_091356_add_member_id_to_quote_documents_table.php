<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMemberIdToQuoteDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quote_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('quote_documents', 'member_detail_id')) {
                $table->unsignedBigInteger('member_detail_id')->nullable();
                $table->foreign('member_detail_id')->references('id')->on('health_quote_request_member_details');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
}
