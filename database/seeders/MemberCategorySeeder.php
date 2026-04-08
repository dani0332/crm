<?php

namespace Database\Seeders;

use App\Models\MemberCategory;
use App\Traits\SeedsFirstOrCreateIfMissing;
use Illuminate\Database\Seeder;

class MemberCategorySeeder extends Seeder
{
    use SeedsFirstOrCreateIfMissing;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->disableMemberCategory();
        $this->createMemberCategory();
    }

    private function disableMemberCategory(): void
    {
        $codes = ['Employee', 'Domestic Helper', 'Investor', 'Partner', 'Dependent husband', 'Dependent wife', 'Dependent child', 'Dependent parent', 'INVESTOR_PARTNER', 'GOLDEN_VISA', 'SELF_EMPLOYED_FREELANCE', 'DOMESTIC_WORKER', 'DEPENDENT_SPOUSE', 'DEPENDENT_CHILD', 'DEPENDENT_PARENT', 'DEPENDENT_SIBLING_OR_OTHER_RELATIVES', 'EMPLOYEE_1', 'EMPLOYEE_2'];
        MemberCategory::whereIn('code', $codes)->update(['is_active' => 0]);
    }

    private function createMemberCategory(): void
    {
        $now = now();
        $this->seedFirstOrCreateIfMissing(MemberCategory::class, [
            [
                'code' => 'DIPLOMAT_PASSPORT',
                'text' => 'Diplomat-Passport',
                'is_active' => 1,
                'sort_order' => 19,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'EXPAT_DUBAI_VISA',
                'text' => 'Expat (Dubai Visa)',
                'is_active' => 1,
                'sort_order' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'EXPAT_NON_DUBAI_VISA',
                'text' => 'Expat (Non-Dubai Visa)',
                'is_active' => 1,
                'sort_order' => 21,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'GCC_NATIONAL',
                'text' => 'GCC National',
                'is_active' => 1,
                'sort_order' => 22,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'UAE_NATIONAL',
                'text' => 'UAE National',
                'is_active' => 1,
                'sort_order' => 23,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'NEWBORN',
                'text' => 'Newborn',
                'is_active' => 1,
                'sort_order' => 24,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
