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

        if (Schema::hasTable('payments')) {
            $keyExists = DB::select(
                DB::raw(
                    'SHOW KEYS
                    FROM payments
                    WHERE Key_name=\'PRIMARY\''
                )
            );
            if (! $keyExists) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->primary('code');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
}
