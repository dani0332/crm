<?php

namespace Database\Seeders;

use App\Models\PaymentStatus;
use Illuminate\Database\Seeder;

class PaymentStatusAddSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $recordExists = PaymentStatus::where('id', '13')->first();
        if (! $recordExists) {
            PaymentStatus::create([
                'id' => '13',
                'code' => 'disputed',
                'text' => 'DISPUTED',
                'sort_order' => '13',
                'is_active' => true,
            ]);
        }

        $recordExists = PaymentStatus::where('id', '14')->first();
        if (! $recordExists) {
            PaymentStatus::create([
                'id' => '14',
                'code' => 'new',
                'text' => 'NEW',
                'sort_order' => '14',
                'is_active' => true,
            ]);
        }

        $recordExists = PaymentStatus::where('id', '15')->first();
        if (! $recordExists) {
            PaymentStatus::create([
                'id' => '15',
                'code' => 'overdue',
                'text' => 'OVERDUE',
                'sort_order' => '15',
                'is_active' => true,
            ]);
        }

        $recordExists = PaymentStatus::where('id', '16')->first();
        if (! $recordExists) {
            PaymentStatus::create([
                'id' => '16',
                'code' => 'credit_approved',
                'text' => 'CREDIT_APPROVED',
                'sort_order' => '16',
                'is_active' => true,
            ]);
        }

        $recordExists = PaymentStatus::where('id', '17')->first();
        if (! $recordExists) {
            PaymentStatus::create([
                'id' => '17',
                'code' => 'partially_paid',
                'text' => 'PARTIALLY_PAID',
                'sort_order' => '17',
                'is_active' => true,
            ]);
        }
    }
}
