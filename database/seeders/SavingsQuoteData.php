<?php

namespace Database\Seeders;

use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\QuoteType;
use App\Models\Role;
use Illuminate\Database\Seeder;

class SavingsQuoteData extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->upsertQuoteType();
        $this->seedRoles();
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

    private function seedRoles()
    {
        $roles = [RolesEnum::SavingsAdvisor, RolesEnum::SavingsManager];

        foreach ($roles as $role) {
            $this->createRole($role);
        }
    }

    private function createRole(string $role)
    {
        return Role::firstOrCreate(['name' => $role]);
    }
}
