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
        $paymentMethodsCount = PaymentMethod::all()->count();
        if ($paymentMethodsCount == 0) {
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
                'name' => 'Approved by General Manager - credit period granted - with email approval',
                'description' => 'Approved by General Manager - credit period granted - with email approval',
                'parent_code' => 'CR',
                'is_active' => true,
            ]);
            PaymentMethod::create([
                'code' => 'CR_HITESH',
                'name' => 'Approved by Chief Marketing Officer - credit period granted - with email approval',
                'description' => 'Approved by Chief Marketing Officer - credit period granted - with email approval',
                'parent_code' => 'CR',
                'is_active' => true,
            ]);
            PaymentMethod::create([
                'code' => 'CR_MAHESH',
                'name' => 'Approved by Chief Operations Officer - credit period granted - with email approval',
                'description' => 'Approved by Chief Operations Officer - credit period granted - with email approval',
                'parent_code' => 'CR',
                'is_active' => true,
            ]);
            PaymentMethod::create([
                'code' => 'IN_PL',
                'name' => 'Insure Now, Pay Later',
                'description' => 'Insure Now, Pay Later',
                'is_active' => true,
            ]);
        }
    }
}
