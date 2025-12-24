<?php

namespace Tests\Helpers\Payments;

use App\Enums\PermissionsEnum;
use App\Models\CarPlan;
use App\Models\CarQuote;
use App\Models\InsuranceProvider;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Helper class for setting up test data for payment tests.
 * Handles creation of test entities and permissions setup.
 */
class PaymentTestDataHelper
{
    /**
     * Set up test data: InsuranceProvider, CarPlan, and CarQuote.
     * Creates all necessary test data for payment testing.
     *
     * @param  string  $quoteCode  Optional quote code (default: 'CAR-ABCDEF12345')
     * @param  string  $quoteUuid  Optional quote UUID (default: 'ABCDEF12345')
     * @return array Returns array with 'insuranceProvider', 'carPlan', 'carQuote', 'quoteCode', 'quoteUuid'
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

        // Create CarQuote using factory method that handles SQLite connection
        $carQuote = CarQuote::factory()->createForSqlite([
            'uuid' => $quoteUuid,
            'code' => $quoteCode,
            'insurance_provider_id' => $insuranceProvider->id,
            'plan_id' => $carPlan->id,
        ]);

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
     * Creates and assigns required permissions to the Admin role and user.
     * This includes permissions needed for payment approval/decline operations.
     *
     * @param  User  $user  The user to assign permissions to
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

        // Get or create Admin role on SQLite connection
        $adminRole = Role::on('sqlite')->firstOrCreate(
            ['name' => \App\Enums\RolesEnum::Admin, 'guard_name' => 'web'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        // Create and assign each permission
        foreach ($permissions as $permissionName) {
            // Create permission on SQLite connection
            $permission = Permission::on('sqlite')->firstOrCreate(
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
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Refresh user to ensure permissions are loaded
        $user->refresh();
    }
}
