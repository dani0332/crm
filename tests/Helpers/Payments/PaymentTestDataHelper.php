<?php

namespace Tests\Helpers\Payments;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\CarPlan;
use App\Models\CarQuote;
use App\Models\InsuranceProvider;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Payment test data helper.
 */
class PaymentTestDataHelper
{
    /**
     * Set up test data: InsuranceProvider, CarPlan, and CarQuote.
     */
    public static function setupTestData(
        string $quoteCode = 'CAR-ABCDEF12345',
        string $quoteUuid = 'ABCDEF12345'
    ): array {
        // Create InsuranceProvider using model-based creation
        $providerAttributes = InsuranceProvider::factory()->definition();
        $insuranceProvider = InsuranceProvider::forceCreate($providerAttributes);

        // Create CarPlan linked to the InsuranceProvider using model-based creation
        $planFactory = CarPlan::factory();
        $planAttributes = $planFactory->definition();
        // Replace factory relationship with actual provider ID
        $planAttributes['provider_id'] = $insuranceProvider->id;
        $carPlan = CarPlan::forceCreate($planAttributes);

        // Create CarQuote using factory
        $quoteAttributes = CarQuote::factory()->definition();
        $quoteAttributes['uuid'] = $quoteUuid;
        $quoteAttributes['code'] = $quoteCode;
        $quoteAttributes['insurance_provider_id'] = $insuranceProvider->id;
        $quoteAttributes['plan_id'] = $carPlan->id;
        $carQuote = CarQuote::forceCreate($quoteAttributes);

        return [
            'insuranceProvider' => $insuranceProvider,
            'carPlan' => $carPlan,
            'carQuote' => $carQuote,
            'quoteCode' => $quoteCode,
            'quoteUuid' => $quoteUuid,
        ];
    }

    /**
     * Set up payment-related permissions for testing.
     */
    public static function setupPaymentPermissions(User $user): void
    {
        // Required permissions for payment operations
        // These permissions are checked in SplitPaymentUpdateRequest validation
        $permissions = [
            PermissionsEnum::PAYMENT_VERIFICATION_COLLECTED_BY_INSURER, // Required for insurer collection type approval
            PermissionsEnum::PAYMENT_VERIFICATION_COLLECTED_BY_BROKER,  // Required for broker collection type approval
            PermissionsEnum::INPL_APPROVER,                              // Required for INPL payment method approval
        ];

        // Get or create Admin role
        // Uses default connection (SQLite in tests as configured in phpunit.xml)
        $adminRole = Role::firstOrCreate(
            ['name' => RolesEnum::Admin, 'guard_name' => 'web'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        // Create and assign each permission
        foreach ($permissions as $permissionName) {
            // Create permission
            // Uses default connection (SQLite in tests as configured in phpunit.xml)
            $permission = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['created_at' => now(), 'updated_at' => now()]
            );

            // Assign permission to Admin role
            $adminRole->givePermissionTo($permission);

            // Assign permission directly to user as well
            $user->refresh();
            $user->givePermissionTo($permission);
        }

        // Clear permission cache to ensure permissions are available immediately
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Refresh user to ensure permissions are loaded
        $user->refresh();
    }
}
