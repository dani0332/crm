<?php

namespace Database\Seeders;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use Illuminate\Database\Seeder;

class CarLostStorageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = [
            ['key_name' => ApplicationStorageEnums::CAR_SOLD_STATUS_REJECTION_TEMPLATE, 'value' => 472],
            ['key_name' => ApplicationStorageEnums::UNCONTACTABLE_STATUS_REJECTION_TEMPLATE, 'value' => 473],
            ['key_name' => ApplicationStorageEnums::CAR_SOLD_RESUBMISSIONS_TEMPLATE, 'value' => 474],
            ['key_name' => ApplicationStorageEnums::UNCON_RENEWALS_REMINDER_TEMPLATE, 'value' => 478],
            ['key_name' => ApplicationStorageEnums::CAR_SOLD_RESUBMISSIONS_TO, 'value' => 'renewalsapprovals@insurancemarket.ae'],
            ['key_name' => ApplicationStorageEnums::CAR_SOLD_RESUBMISSIONS_CC, 'value' => 'marketing.operations@insurancemarket.ae'],
            ['key_name' => ApplicationStorageEnums::CAR_LOST_REJECTION_EMAIL_CC, 'value' => 'renewalsapprovals@insurancemarket.ae'],
        ];

        foreach ($items as $storage) {
            if (! ApplicationStorage::where('key_name', $storage['key_name'])->first()) {
                ApplicationStorage::insert([
                    'key_name' => $storage['key_name'],
                    'value' => $storage['value'],
                    'is_active' => 1,
                ]);
            }
        }

    }
}
