<?php

namespace Tests\Helpers;

use App\Models\Nationality;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder
{
    /**
     * Create a test user with authentication.
     */
    public static function createUser(array $attributes = []): User
    {
        $defaults = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ];

        $user = User::factory()->create(array_merge($defaults, $attributes));
        $user->setConnection('sqlite_testing');

        return $user;
    }

    /**
     * Create a user with a specific role.
     */
    public static function createUserWithRole(string $roleName, array $attributes = []): User
    {
        $user = self::createUser($attributes);

        // Create role if it doesn't exist using DB facade
        $db = \Illuminate\Support\Facades\DB::connection('sqlite_testing');
        $roleId = $db->table('roles')->where('name', $roleName)->value('id');
        if (! $roleId) {
            $roleId = $db->table('roles')->insertGetId([
                'name' => $roleName,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Assign role
        $db->table('model_has_roles')->insertOrIgnore([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);

        return $user;
    }

    /**
     * Seed required lookup data for LifeQuote tests.
     *
     * @return array Array of created lookup IDs
     */
    public static function seedLifeQuoteLookups(): array
    {
        // Use DB facade to insert directly and avoid mass assignment issues
        $db = \Illuminate\Support\Facades\DB::connection('sqlite_testing');

        // Create Nationality
        $nationalityId = $db->table('nationality')->where('text', 'Test Nationality')->value('id');
        if (! $nationalityId) {
            $nationalityId = $db->table('nationality')->insertGetId([
                'text' => 'Test Nationality',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create Currency
        $currencyId = $db->table('currency_type')->where('text', 'AED')->value('id');
        if (! $currencyId) {
            $currencyId = $db->table('currency_type')->insertGetId([
                'text' => 'AED',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create Marital Status
        $maritalStatusId = $db->table('marital_status')->where('text', 'Single')->value('id');
        if (! $maritalStatusId) {
            $maritalStatusId = $db->table('marital_status')->insertGetId([
                'text' => 'Single',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create Life Insurance Purpose
        $purposeId = $db->table('life_insurance_purpose')->where('text', 'Test Purpose')->value('id');
        if (! $purposeId) {
            $purposeId = $db->table('life_insurance_purpose')->insertGetId([
                'text' => 'Test Purpose',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create Life Number of Years
        $numberOfYearsId = $db->table('life_number_of_year')->where('text', '10 Years')->value('id');
        if (! $numberOfYearsId) {
            $numberOfYearsId = $db->table('life_number_of_year')->insertGetId([
                'text' => '10 Years',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [
            'nationality_id' => $nationalityId,
            'currency_id' => $currencyId,
            'marital_status_id' => $maritalStatusId,
            'purpose_of_insurance_id' => $purposeId,
            'number_of_years_id' => $numberOfYearsId,
        ];
    }

    /**
     * Create a user with Admin role for testing.
     */
    public static function createAdminUser(array $attributes = []): User
    {
        $user = self::createUser($attributes);

        $db = \Illuminate\Support\Facades\DB::connection('sqlite_testing');
        $roleId = $db->table('roles')->where('name', \App\Enums\RolesEnum::Admin)->value('id');
        if (! $roleId) {
            $roleId = $db->table('roles')->insertGetId([
                'name' => \App\Enums\RolesEnum::Admin,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $db->table('model_has_roles')->insertOrIgnore([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);

        return $user;
    }
}
