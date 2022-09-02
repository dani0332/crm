<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddIdToDocumentTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('document_types');

        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
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

            $table->index('code');
            $table->index('is_active');
        });

        $travelPolicySchedule = DB::table('document_types')->where('code', 'TravelPolicySchedule')->first();
        if ($travelPolicySchedule === null) {
            DB::table('document_types')->insert([
                'code' => 'TPC',
                'text' => 'Policy Certificate',
                'max_files' => 1,
                'max_size' => 5,
                'folder_path' => 'car',
                'accepted_files' => '.xlsm,.xlsx,.pdf,.jpeg,.jpg',
                'quote_type_id' => 1,
                'send_to_customer' => true,
                'sort_order' => 1,
            ]);
        }

        $travelDebitNote = DB::table('document_types')->where('code', 'TravelDebitNote')->first();
        if ($travelDebitNote === null) {
            DB::table('document_types')->insert([
                'code' => 'TTI',
                'text' => 'Tax Invoice',
                'max_files' => 1,
                'max_size' => 5,
                'folder_path' => 'car',
                'accepted_files' => '.xlsm,.xlsx,.pdf,.jpeg,.jpg',
                'quote_type_id' => 1,
                'send_to_customer' => true,
                'sort_order' => 2,
            ]);
        }

        $travelEmiratesId = DB::table('document_types')->where('code', 'TravelEmiratesId')->first();
        if ($travelEmiratesId === null) {
            DB::table('document_types')->insert([
                'code' => 'TTIRBB',
                'text' => 'Tax Invoice Raise by Buyer',
                'max_files' => 2,
                'max_size' => 5,
                'folder_path' => 'car',
                'accepted_files' => '.xlsm,.xlsx,.pdf,.jpeg,.jpg',
                'quote_type_id' => 1,
                'send_to_customer' => false,
                'sort_order' => 3,
            ]);
        }

        $travelOther = DB::table('document_types')->where('code', 'TravelOther')->first();
        if ($travelOther === null) {
            DB::table('document_types')->insert([
                'code' => 'TEID',
                'text' => 'Emirates ID',
                'max_files' => 20,
                'max_size' => 5,
                'folder_path' => 'car',
                'accepted_files' => '.xlsm,.xlsx,.pdf,.jpeg,.jpg',
                'quote_type_id' => 1,
                'send_to_customer' => false,
                'sort_order' => 4,
            ]);
        }

        $travelOther = DB::table('document_types')->where('code', 'TravelOther')->first();
        if ($travelOther === null) {
            DB::table('document_types')->insert([
                'code' => 'TR',
                'text' => 'Receipt',
                'max_files' => 20,
                'max_size' => 5,
                'folder_path' => 'car',
                'accepted_files' => '.xlsm,.xlsx,.pdf,.jpeg,.jpg',
                'quote_type_id' => 1,
                'send_to_customer' => false,
                'sort_order' => 5,
            ]);
        }

        $travelOther = DB::table('document_types')->where('code', 'TravelOther')->first();
        if ($travelOther === null) {
            DB::table('document_types')->insert([
                'code' => 'TAD',
                'text' => 'Additional Documents',
                'max_files' => 20,
                'max_size' => 5,
                'folder_path' => 'car',
                'accepted_files' => '.xlsm,.xlsx,.pdf,.jpeg,.jpg',
                'quote_type_id' => 1,
                'send_to_customer' => false,
                'sort_order' => 6,
            ]);
        }

        $travelOther = DB::table('document_types')->where('code', 'TravelOther')->first();
        if ($travelOther === null) {
            DB::table('document_types')->insert([
                'code' => 'TAEA',
                'text' => 'Additional Email Attachments',
                'max_files' => 20,
                'max_size' => 5,
                'folder_path' => 'car',
                'accepted_files' => '.xlsm,.xlsx,.pdf,.jpeg,.jpg',
                'quote_type_id' => 1,
                'send_to_customer' => false,
                'sort_order' => 7,
            ]);
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->primary('code');
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
    }
}
