<?php

namespace Database\Seeders;

use App\Enums\PermissionsEnum;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    private const WEB_GUARD = 'web';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedEditPlanAfterTransactionApprovalPermission();
    }

    /**
     * Idempotent {@see Permission} row. Do not set created_at/updated_at here — Eloquent
     * applies timestamps on insert when the model uses timestamps (Spatie Permission does).
     */
    private function seedEditPlanAfterTransactionApprovalPermission(): void
    {
        Permission::firstOrCreate(
            [
                'name' => PermissionsEnum::EDIT_PLAN_AFTER_TRANSACTION_APPROVAL,
                'guard_name' => self::WEB_GUARD,
            ],
        );
    }
}
