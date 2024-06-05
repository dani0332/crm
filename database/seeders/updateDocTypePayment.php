<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class updateDocTypePayment extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::table('document_types')
            ->where('code', 'CPD')
            ->update([
                'text' => 'Payment Proof',
            ]);
    }
}
