<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamTypeEnum;
use App\Models\QuoteStatus;
use App\Models\QuoteStatusMap;
use App\Models\QuoteType;
use App\Models\Team;
use Database\Seeders\Traits\PermissionableSeeder;
use Illuminate\Database\Seeder;

class SavingsQuoteDataSeeder extends Seeder
{
    use PermissionableSeeder;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->upsertQuoteType();
        $this->mapQuoteStatuses();

        $this->seedRoles([RolesEnum::SavingsAdvisor, RolesEnum::SavingsManager]);
        $this->seedPermissions([
            PermissionsEnum::SAVINGS_QUOTES_LIST,
            PermissionsEnum::SAVINGS_QUOTES_CREATE,
            PermissionsEnum::SAVINGS_QUOTES_EDIT,
            PermissionsEnum::SAVINGS_QUOTES_SHOW,
            PermissionsEnum::SAVINGS_COMPREHENSIVE_DASHBOARD,
            PermissionsEnum::SAVINGS_CONVERSION_REPORT,
            PermissionsEnum::SAVINGS_DISTRIBUTION_REPORT,
            PermissionsEnum::SAVINGS_LEAD_ALLOCATION_DASHBOARD,
        ], [RolesEnum::Engineering, RolesEnum::Admin]);
        $this->product();
    }

    private function upsertQuoteType()
    {
        $quoteType = [
            'short_code' => QuoteTypes::SAVINGS->shortCode(),
            'code' => QuoteTypes::SAVINGS->value,
            'text' => 'Savings Insurance',
            'is_active' => 1,
        ];

        if (! QuoteType::where('code', $quoteType['code'])->exists()) {
            QuoteType::create($quoteType);
        } else {
            QuoteType::where('code', $quoteType['code'])->update($quoteType);
        }
    }

    private function mapQuoteStatuses()
    {
        $quoteStatuses = QuoteStatus::oldest()->get();

        $sortOrder = 0;
        $quoteStatuses->each(function ($quoteStatus) use (&$sortOrder) {
            $quoteStatusMap = QuoteStatusMap::where('quote_status_id', $quoteStatus->id)
                ->where('quote_type_id', QuoteTypes::SAVINGS->id())
                ->exists();

            if (! $quoteStatusMap) {
                $sortOrder++;
                QuoteStatusMap::create([
                    'quote_status_id' => $quoteStatus->id,
                    'quote_type_id' => QuoteTypes::SAVINGS->id(),
                    'sort_order' => $sortOrder,
                    'created_by' => 'usman.iqbal@myalfred.com',
                    'updated_by' => 'usman.iqbal@myalfred.com',
                ]);
            }
        });
    }

    private function product()
    {
        if (! Team::where('code', 'Savings')->exists()) {
            Team::create([
                'name' => 'Savings',
                'code' => 'Savings',
                'type' => TeamTypeEnum::PRODUCT,
                'is_active' => 1,
            ]);
        }
    }
}
