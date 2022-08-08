<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $paymentMethods = PaymentMethod::all();
        if ($paymentMethods === null) {
            PaymentMethod::create([
                'code' => 'BT',
                'name' => 'Bank Transfer',
                'description' => 'Bank Transfer',
                'is_active' => true,
            ]);
            PaymentMethod::create([
                'code' => 'CSH',
                'name' => 'Cash',
                'description' => 'Cash',
                'is_active' => true,
            ]);
            PaymentMethod::create([
                'code' => 'CHQ',
                'name' => 'Cheque',
                'description' => 'Cheque',
                'is_active' => true,
            ]);
            PaymentMethod::create([
                'code' => 'CC',
                'name' => 'Credit Card',
                'description' => 'Credit Card',
                'is_active' => true,
            ]);
            PaymentMethod::create([
                'code' => 'CR',
                'name' => 'Credit',
                'description' => 'Credit',
                'is_active' => true,
            ]);
            PaymentMethod::create([
                'code' => 'CR_FAYAZ',
                'name' => 'Unpaid but approved by Fayaz - credit period granted - with email approval',
                'description' => 'Unpaid but approved by Fayaz - credit period granted - with email approval',
                'parent_code' => 'CR',
                'is_active' => true,
            ]);
            PaymentMethod::create([
                'code' => 'CR_HITESH',
                'name' => 'Unpaid but approved by Hitesh - credit period granted - with email approval',
                'description' => 'Unpaid but approved by Hitesh - credit period granted - with email approval',
                'parent_code' => 'CR',
                'is_active' => true,
            ]);
            PaymentMethod::create([
                'code' => 'CR_MAHESH',
                'name' => 'Unpaid but approved by Mahesh - credit period granted - with email approval',
                'description' => 'Unpaid but approved by Mahesh - credit period granted - with email approval',
                'parent_code' => 'CR',
                'is_active' => true,
            ]);
        }
    }
}
