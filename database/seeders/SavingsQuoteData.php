<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\QuoteType;
use Database\Seeders\Traits\PermissionableSeeder;
use Illuminate\Database\Seeder;

class SavingsQuoteData extends Seeder
{
    use PermissionableSeeder;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->upsertQuoteType();
        $this->seedRoles([RolesEnum::SavingsAdvisor, RolesEnum::SavingsManager]);
        $this->seedPermissions([
            PermissionsEnum::SAVINGS_QUOTES_LIST,
            PermissionsEnum::SAVINGS_QUOTES_CREATE,
            PermissionsEnum::SAVINGS_QUOTES_EDIT,
            PermissionsEnum::SAVINGS_QUOTES_SHOW,
        ], [RolesEnum::Engineering, RolesEnum::Admin]);
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
}
