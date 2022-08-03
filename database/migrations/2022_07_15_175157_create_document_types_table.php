<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('document_types')) {
            Schema::create('document_types', function (Blueprint $table) {
                $table->string('code', '10');
                $table->string('text', '100');
                $table->boolean('is_active')->default(true);
                $table->integer('quote_type_id')->nullable();
                $table->string('folder_path', '255')->nullable();
                $table->string('accepted_files', '255')->nullable();
                $table->integer('max_files')->default('1');
                $table->integer('max_size')->default('5');
                $table->boolean('is_required')->default(false);
                $table->boolean('send_to_customer')->default(false);
                $table->integer('sort_order')->nullable();
                $table->primary('code');
            });

            $travelPolicySchedule = DB::table('document_types')->where('code', 'TravelPolicySchedule')->first();
            if ($travelPolicySchedule === null) {
                DB::table('document_types')->insert([
                    'code' => 'TPS',
                    'text' => 'Policy Schedule',
                    'max_files' => 1,
                    'max_size' => 5,
                    'folder_path' => 'travel',
                    'accepted_files' => '.xlsm,.xlsx,.pdf,.jpeg,.jpg',
                    'quote_type_id' => 8,
                    'send_to_customer' => true,
                    'sort_order' => 1,
                ]);
            }

            $travelDebitNote = DB::table('document_types')->where('code', 'TravelDebitNote')->first();
            if ($travelDebitNote === null) {
                DB::table('document_types')->insert([
                    'code' => 'TDN',
                    'text' => 'Debit Note',
                    'max_files' => 1,
                    'max_size' => 5,
                    'folder_path' => 'travel',
                    'accepted_files' => '.xlsm,.xlsx,.pdf,.jpeg,.jpg',
                    'quote_type_id' => 8,
                    'send_to_customer' => true,
                    'sort_order' => 2,
                ]);
            }

            $travelEmiratesId = DB::table('document_types')->where('code', 'TravelEmiratesId')->first();
            if ($travelEmiratesId === null) {
                DB::table('document_types')->insert([
                    'code' => 'TEID',
                    'text' => 'Emirates Id',
                    'max_files' => 2,
                    'max_size' => 5,
                    'folder_path' => 'travel',
                    'accepted_files' => '.xlsm,.xlsx,.pdf,.jpeg,.jpg',
                    'quote_type_id' => 8,
                    'send_to_customer' => false,
                    'sort_order' => 3,
                ]);
            }

            $travelOther = DB::table('document_types')->where('code', 'TravelOther')->first();
            if ($travelOther === null) {
                DB::table('document_types')->insert([
                    'code' => 'TO',
                    'text' => 'Other',
                    'max_files' => 20,
                    'max_size' => 5,
                    'folder_path' => 'travel',
                    'accepted_files' => '.xlsm,.xlsx,.pdf,.jpeg,.jpg',
                    'quote_type_id' => 8,
                    'send_to_customer' => false,
                    'sort_order' => 4,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('document_types');
    }
}
