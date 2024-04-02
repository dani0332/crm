<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateEmbeddedTransactions extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('embedded_transactions')
            ->where('quote_type_id', 1)
            ->where('quote_request_type', null)
            ->update(['quote_request_type' => 'App\\Models\\CarQuote']);
    }
}
