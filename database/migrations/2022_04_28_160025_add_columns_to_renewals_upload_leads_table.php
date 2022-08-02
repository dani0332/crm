<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToRenewalsUploadLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renewals_upload_leads', function (Blueprint $table) {
            if (! Schema::hasColumn('renewals_upload_leads', 'renewal_import_code')) {
                $table->string('renewal_import_code', '20')->nullable();
                $table->index('renewal_import_code', 'idx_renewal_import_code');
            }
            if (! Schema::hasColumn('renewals_upload_leads', 'renewal_import_type')) {
                $table->string('renewal_import_type', '100')->nullable();
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
        Schema::table('renewals_upload_leads', function (Blueprint $table) {
            //
        });
    }
}
