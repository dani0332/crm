<?php

namespace Database\Seeders;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\QuoteStatusMap;
use Illuminate\Database\Seeder;

class QuoteStatusMapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $quoteStatuses = [
            [
                'quote_type_id' => QuoteTypeId::Yacht,
                'quote_status_id' => QuoteStatusEnum::CancellationPending,
                'sort_order' => 9,
                'created_by' => 'mirza.baig@myalfred.com',
                'updated_by' => 'mirza.baig@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Life,
                'quote_status_id' => QuoteStatusEnum::CancellationPending,
                'sort_order' => 9,
                'created_by' => 'mirza.baig@myalfred.com',
                'updated_by' => 'mirza.baig@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Car,
                'quote_status_id' => QuoteStatusEnum::PolicyIssued,
                'sort_order' => QuoteTypeId::Car,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Car,
                'quote_status_id' => QuoteStatusEnum::PolicySentToCustomer,
                'sort_order' => QuoteTypeId::Car,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Car,
                'quote_status_id' => QuoteStatusEnum::PolicyBooked,
                'sort_order' => QuoteTypeId::Car,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Home,
                'quote_status_id' => QuoteStatusEnum::PolicyIssued,
                'sort_order' => QuoteTypeId::Home,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Home,
                'quote_status_id' => QuoteStatusEnum::PolicySentToCustomer,
                'sort_order' => QuoteTypeId::Home,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Home,
                'quote_status_id' => QuoteStatusEnum::PolicyBooked,
                'sort_order' => QuoteTypeId::Home,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Health,
                'quote_status_id' => QuoteStatusEnum::PolicyIssued,
                'sort_order' => QuoteTypeId::Health,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Health,
                'quote_status_id' => QuoteStatusEnum::PolicySentToCustomer,
                'sort_order' => QuoteTypeId::Health,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Health,
                'quote_status_id' => QuoteStatusEnum::PolicyBooked,
                'sort_order' => QuoteTypeId::Health,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Life,
                'quote_status_id' => QuoteStatusEnum::PolicyIssued,
                'sort_order' => QuoteTypeId::Life,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Life,
                'quote_status_id' => QuoteStatusEnum::PolicySentToCustomer,
                'sort_order' => QuoteTypeId::Life,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Life,
                'quote_status_id' => QuoteStatusEnum::PolicyBooked,
                'sort_order' => QuoteTypeId::Life,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Business,
                'quote_status_id' => QuoteStatusEnum::PolicyIssued,
                'sort_order' => QuoteTypeId::Business,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Business,
                'quote_status_id' => QuoteStatusEnum::PolicySentToCustomer,
                'sort_order' => QuoteTypeId::Business,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Business,
                'quote_status_id' => QuoteStatusEnum::PolicyBooked,
                'sort_order' => QuoteTypeId::Business,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Bike,
                'quote_status_id' => QuoteStatusEnum::PolicyIssued,
                'sort_order' => QuoteTypeId::Bike,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Bike,
                'quote_status_id' => QuoteStatusEnum::PolicySentToCustomer,
                'sort_order' => QuoteTypeId::Bike,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Bike,
                'quote_status_id' => QuoteStatusEnum::PolicyBooked,
                'sort_order' => QuoteTypeId::Bike,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Yacht,
                'quote_status_id' => QuoteStatusEnum::PolicyIssued,
                'sort_order' => QuoteTypeId::Yacht,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Yacht,
                'quote_status_id' => QuoteStatusEnum::PolicySentToCustomer,
                'sort_order' => QuoteTypeId::Yacht,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Yacht,
                'quote_status_id' => QuoteStatusEnum::PolicyBooked,
                'sort_order' => QuoteTypeId::Yacht,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Travel,
                'quote_status_id' => QuoteStatusEnum::PolicyIssued,
                'sort_order' => QuoteTypeId::Travel,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Travel,
                'quote_status_id' => QuoteStatusEnum::PolicySentToCustomer,
                'sort_order' => QuoteTypeId::Travel,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Travel,
                'quote_status_id' => QuoteStatusEnum::PolicyBooked,
                'sort_order' => QuoteTypeId::Travel,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Pet,
                'quote_status_id' => QuoteStatusEnum::PolicyIssued,
                'sort_order' => QuoteTypeId::Pet,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Pet,
                'quote_status_id' => QuoteStatusEnum::PolicySentToCustomer,
                'sort_order' => QuoteTypeId::Pet,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Pet,
                'quote_status_id' => QuoteStatusEnum::PolicyBooked,
                'sort_order' => QuoteTypeId::Pet,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Cycle,
                'quote_status_id' => QuoteStatusEnum::PolicyIssued,
                'sort_order' => QuoteTypeId::Cycle,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Cycle,
                'quote_status_id' => QuoteStatusEnum::PolicySentToCustomer,
                'sort_order' => QuoteTypeId::Cycle,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Cycle,
                'quote_status_id' => QuoteStatusEnum::PolicyBooked,
                'sort_order' => QuoteTypeId::Cycle,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Jetski,
                'quote_status_id' => QuoteStatusEnum::PolicyIssued,
                'sort_order' => QuoteTypeId::Jetski,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Jetski,
                'quote_status_id' => QuoteStatusEnum::PolicySentToCustomer,
                'sort_order' => QuoteTypeId::Jetski,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Jetski,
                'quote_status_id' => QuoteStatusEnum::PolicyBooked,
                'sort_order' => QuoteTypeId::Jetski,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Corpline,
                'quote_status_id' => QuoteStatusEnum::PolicyIssued,
                'sort_order' => QuoteTypeId::Corpline,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Corpline,
                'quote_status_id' => QuoteStatusEnum::PolicySentToCustomer,
                'sort_order' => QuoteTypeId::Corpline,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::Corpline,
                'quote_status_id' => QuoteStatusEnum::PolicyBooked,
                'sort_order' => QuoteTypeId::Corpline,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::GroupMedical,
                'quote_status_id' => QuoteStatusEnum::PolicyIssued,
                'sort_order' => QuoteTypeId::GroupMedical,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::GroupMedical,
                'quote_status_id' => QuoteStatusEnum::PolicySentToCustomer,
                'sort_order' => QuoteTypeId::GroupMedical,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
            [
                'quote_type_id' => QuoteTypeId::GroupMedical,
                'quote_status_id' => QuoteStatusEnum::PolicyBooked,
                'sort_order' => QuoteTypeId::GroupMedical,
                'created_by' => 'muhammad.waris@myalfred.com',
                'updated_by' => 'muhammad.waris@myalfred.com',
            ],
        ];

        foreach ($quoteStatuses as $quoteStatus) {
            $whereCondition = ['quote_type_id' => $quoteStatus['quote_type_id'], 'quote_status_id' => $quoteStatus['quote_status_id']];
            QuoteStatusMap::firstOrCreate($whereCondition, $quoteStatus);
        }
    }
}
