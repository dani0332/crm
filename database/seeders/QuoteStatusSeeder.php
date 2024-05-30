<?php

namespace Database\Seeders;

use App\Models\QuoteStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuoteStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $quoteStatusSeeder = [
            [
                "code" => "Policy Issued",
                "text" => "Policy Issued",
                "text_ar" => null,
                "is_active" => 1,
                "sort_order" => 23,
                "is_deleted" => 0,
                "created_at" => "2021-12-26 11:39:42",
                "updated_at" => "2021-12-26 11:39:42",
                "deleted_at" => null,
                "uuid" => "0a9232db-6612-11ec-b285-8f7ab6218021",
                "created_by" => "saroosh.hameed@afia.ae",
                "updated_by" => "saroosh.hameed@afia.ae"
            ],
            [
                "code" => "PolicySentToCustomer",
                "text" => "Policy Sent to Customer",
                "text_ar" => "Policy Sent to Customer",
                "is_active" => 1,
                "sort_order" => 19,
                "is_deleted" => 0,
                "created_at" => "2024-01-30 16:48:52",
                "updated_at" => "2024-01-30 16:48:52",
                "deleted_at" => null,
                "uuid" => "ea826923-bf6d-11ee-a8a6-2a23318a2517",
                "created_by" => "nouman.hussain@insurancemarket.ae",
                "updated_by" => "nouman.hussain@insurancemarket.ae"
            ],
            [
                "code" => "PolicyBooked",
                "text" => "Policy Booked",
                "text_ar" => "Policy Booked",
                "is_active" => 1,
                "sort_order" => 20,
                "is_deleted" => 0,
                "created_at" => "2024-01-30 16:48:52",
                "updated_at" => "2024-01-30 16:48:52",
                "deleted_at" => null,
                "uuid" => "eaa01b0f-bf6d-11ee-a8a6-2a23318a2517",
                "created_by" => "nouman.hussain@insurancemarket.ae",
                "updated_by" => "nouman.hussain@insurancemarket.ae"
            ]
        ];
        
        foreach ($quoteStatusSeeder as $quoteStatus) {
            $conditions = [
                'code' => $quoteStatus['code'],
            ];
            QuoteStatus::firstOrCreate($conditions, $quoteStatus);
        }
    }
}
