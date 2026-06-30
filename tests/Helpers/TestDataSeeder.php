<?php

namespace Tests\Helpers;

use App\Enums\ApplicationStorageEnums;
use App\Enums\AuthGuardEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamTypeEnum;
use App\Models\CarQuote;
use App\Models\Nationality;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class TestDataSeeder
{
    /**
     * Create a test user with authentication.
     */
    public static function createUser(array $attributes = []): User
    {
        $defaults = [
            'name' => 'Test User',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            // Required for routes behind CheckLastLoginMiddleware (last_login_check)
            'last_login' => now(),
        ];

        $user = User::factory()->create(array_merge($defaults, $attributes));
        $user->setConnection('sqlite');

        return $user;
    }

    /**
     * Create a user with a specific role.
     */
    public static function createUserWithRole(string $roleName, array $attributes = []): User
    {
        $user = self::createUser($attributes);

        // Create role if it doesn't exist using DB facade
        $db = DB::connection('sqlite');
        $roleId = $db->table('roles')
            ->where('name', $roleName)
            ->where('guard_name', AuthGuardEnum::Web->value)
            ->value('id');
        if (! $roleId) {
            $roleId = $db->table('roles')->insertGetId([
                'name' => $roleName,
                'guard_name' => AuthGuardEnum::Web->value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Assign role
        $now = now();
        $db->table('model_has_roles')->insertOrIgnore([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $user->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $user;
    }

    /**
     * Seed permissions for a single role (Spatie permissions tables) on sqlite.
     *
     * @param  array<int, string>  $permissionNames
     */
    public static function seedRolePermissions(string $roleName, array $permissionNames): void
    {
        $db = DB::connection('sqlite');

        $guardName = AuthGuardEnum::Web->value;

        $roleId = $db->table('roles')
            ->where('name', $roleName)
            ->where('guard_name', $guardName)
            ->value('id');

        if (! $roleId) {
            $roleId = $db->table('roles')->insertGetId([
                'name' => $roleName,
                'guard_name' => $guardName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permissionIds = collect($permissionNames)
            ->filter(fn ($permissionName) => is_string($permissionName) && $permissionName !== '')
            ->unique()
            ->map(function (string $permissionName) use ($db, $guardName): int {
                $permissionId = $db->table('permissions')
                    ->where('name', $permissionName)
                    ->where('guard_name', $guardName)
                    ->value('id');

                if (! $permissionId) {
                    $permissionId = $db->table('permissions')->insertGetId([
                        'name' => $permissionName,
                        'guard_name' => $guardName,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                return (int) $permissionId;
            })
            ->values()
            ->all();

        foreach ($permissionIds as $permissionId) {
            $db->table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }

        // Ensure Spatie doesn't serve stale permission mappings in the same process. Ensures cache is cleared so new/updated roles and permissions are recognized immediately
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Seed required lookup data for LifeQuote tests.
     *
     * @return array Array of created lookup IDs
     */
    public static function seedLifeQuoteLookups(): array
    {
        // Use DB facade to insert directly and avoid mass assignment issues
        $db = DB::connection('sqlite');

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
    public static function createAdminUser(array $attributes = [], array $permissionNames = []): User
    {
        $user = self::createUser($attributes);

        $db = DB::connection('sqlite');
        $roleId = $db->table('roles')
            ->where('name', RolesEnum::Admin)
            ->where('guard_name', AuthGuardEnum::Web->value)
            ->value('id');
        if (! $roleId) {
            $roleId = $db->table('roles')->insertGetId([
                'name' => RolesEnum::Admin,
                'guard_name' => AuthGuardEnum::Web->value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $now = now();
        $db->table('model_has_roles')->insertOrIgnore([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $user->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // In app, Admin users are expected to pass permission middleware checks.
        // For SQLite tests, attach all known PermissionsEnum permissions unless explicitly overridden.
        $permissionsToSeed = $permissionNames !== []
            ? $permissionNames
            : array_values(PermissionsEnum::asArray());

        self::seedRolePermissions(RolesEnum::Admin, $permissionsToSeed);

        return $user;
    }

    /**
     * Seed the Bird workflow URL used by AIG workflow (same key as NB motor workflow).
     */
    public static function seedBirdNbMotorWorkflowUrl(string $url = 'https://example.test/workflow'): void
    {
        $db = DB::connection('sqlite');

        $existingId = $db->table('application_storage')
            ->where('key_name', ApplicationStorageEnums::BIRD_NB_MOTOR_WORKFLOW)
            ->value('id');

        if ($existingId) {
            $db->table('application_storage')
                ->where('id', $existingId)
                ->update(['value' => $url, 'updated_at' => now()]);

            return;
        }

        $db->table('application_storage')->insert([
            'key_name' => ApplicationStorageEnums::BIRD_NB_MOTOR_WORKFLOW,
            'value' => $url,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Seed the Bird AccessKey used for access-key authenticated Bird calls.
     */
    public static function seedBirdAccessKey(string $accessKey = 'test-access-key'): void
    {
        $db = DB::connection('sqlite');

        $existingId = $db->table('application_storage')
            ->where('key_name', ApplicationStorageEnums::BIRD_ACCESS_KEY)
            ->value('id');

        if ($existingId) {
            $db->table('application_storage')
                ->where('id', $existingId)
                ->update(['value' => $accessKey, 'updated_at' => now()]);

            return;
        }

        $db->table('application_storage')->insert([
            'key_name' => ApplicationStorageEnums::BIRD_ACCESS_KEY,
            'value' => $accessKey,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Create a minimal CarQuote record for AIG workflow tests (SQLite connection).
     */
    public static function createCarQuote(array $overrides = []): CarQuote
    {
        $defaults = [
            'uuid' => 'test-car-quote-uuid-'.uniqid(),
            'code' => 'TEST-'.uniqid(),
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'customer@example.com',
            'mobile_no' => '0500000000',
            'advisor_id' => null,
            'quote_status_id' => null,
            'aig_flow_executed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $db = DB::connection('sqlite');
        $id = $db->table('car_quote_request')->insertGetId(array_merge($defaults, $overrides));

        return CarQuote::on('sqlite')->findOrFail($id);
    }

    /**
     * Seed required lookup data for CarQuote tests.
     *
     * @return array Array of created lookup IDs
     */
    public static function seedCarQuoteLookups(): array
    {
        $db = DB::connection('sqlite');

        // Create Insurance Provider (RSA)
        $insuranceProviderId = $db->table('insurance_provider')->where('code', InsuranceProvidersEnum::RSA)->value('id');
        if (! $insuranceProviderId) {
            $insuranceProviderId = $db->table('insurance_provider')->insertGetId([
                'code' => InsuranceProvidersEnum::RSA,
                'text' => 'RSA Insurance',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create Car Plan
        $carPlanId = $db->table('car_plan')->insertGetId([
            'provider_id' => $insuranceProviderId,
            'plan_name' => 'Test Car Plan',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'insurance_provider_id' => $insuranceProviderId,
            'plan_id' => $carPlanId,
        ];
    }

    /**
     * Seed application_storage key/value pairs on sqlite.
     *
     * @param  array<string, mixed>  $keyValueMap
     */
    public static function seedApplicationStorage(array $keyValueMap): void
    {
        $rows = collect($keyValueMap)
            ->map(fn ($value, $key) => [
                'key_name' => $key,
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()
            ->all();

        DB::connection('sqlite')
            ->table('application_storage')
            ->insertOrIgnore($rows);
    }

    /**
     * Seed a basic product -> team hierarchy on sqlite.
     *
     * @return array{productTeamId:int, teamId:int}
     */
    public static function seedTeamHierarchy(): array
    {
        $db = DB::connection('sqlite');

        $productTeamId = (int) $db->table('teams')->insertGetId([
            'name' => 'TEST_PRODUCT',
            'type' => TeamTypeEnum::PRODUCT,
            'is_active' => 1,
            'parent_team_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $teamId = (int) $db->table('teams')->insertGetId([
            'name' => 'TEST_TEAM',
            'type' => TeamTypeEnum::TEAM,
            'is_active' => 1,
            'parent_team_id' => $productTeamId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return compact('productTeamId', 'teamId');
    }

    /**
     * Seed required lookup data for Cyber Quote tests.
     *
     * @return array Array of created lookup IDs
     */
    public static function seedCyberQuoteLookups(): array
    {
        $db = DB::connection('sqlite');

        // Create Nationality
        $nationalityId = $db->table('nationality')->where('text', 'United Arab Emirates')->value('id');
        if (! $nationalityId) {
            $nationalityId = $db->table('nationality')->insertGetId([
                'text' => 'United Arab Emirates',
                'code' => 'AE',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create Emirate (note: table is "emirates" not "emirate")
        $emirateId = $db->table('emirates')->where('text', 'Dubai')->value('id');
        if (! $emirateId) {
            $emirateId = $db->table('emirates')->insertGetId([
                'text' => 'Dubai',
                'code' => 'DXB',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [
            'nationality_id' => $nationalityId,
            'emirate_id' => $emirateId,
        ];
    }

    /**
     * Seed device-quotes permissions and assign to Admin role (for DeviceQuote tests).
     */
    public static function seedDeviceQuotePermissions(): void
    {
        $db = DB::connection('sqlite');
        $guard = 'web';
        $names = [
            PermissionsEnum::DEVICE_QUOTES_LIST,
            PermissionsEnum::DEVICE_QUOTES_CREATE,
            PermissionsEnum::DEVICE_QUOTES_EDIT,
            PermissionsEnum::DEVICE_QUOTES_SHOW,
        ];
        $roleId = $db->table('roles')->where('name', RolesEnum::Admin)->value('id');
        if (! $roleId) {
            return;
        }
        foreach ($names as $name) {
            $permId = $db->table('permissions')->where('name', $name)->where('guard_name', $guard)->value('id');
            if (! $permId) {
                $permId = $db->table('permissions')->insertGetId([
                    'name' => $name,
                    'guard_name' => $guard,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $exists = $db->table('role_has_permissions')
                ->where('permission_id', $permId)
                ->where('role_id', $roleId)
                ->exists();
            if (! $exists) {
                $db->table('role_has_permissions')->insert([
                    'permission_id' => $permId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    /**
     * Seed required lookup data for DeviceQuote tests (make/model).
     *
     * @return array{make_id: int, model_id: int}
     */
    public static function seedDeviceQuoteLookups(): array
    {
        $db = DB::connection('sqlite');

        // Device quote type (id=20) required for PersonalQuote->quoteType and redirects
        $db->table('quote_type')->insertOrIgnore([
            'id' => 20,
            'code' => 'Device',
            'short_code' => 'DEV',
            'text' => 'Device Insurance',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $makeId = $db->table('device_make')->where('text', 'Test Make')->value('id');
        if (! $makeId) {
            $makeId = $db->table('device_make')->insertGetId([
                'text' => 'Test Make',
                'name' => 'Test Make',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $modelId = $db->table('device_model')->where('make_id', $makeId)->where('text', 'Test Model')->value('id');
        if (! $modelId) {
            $modelId = $db->table('device_model')->insertGetId([
                'make_id' => $makeId,
                'text' => 'Test Model',
                'name' => 'Test Model',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [
            'make_id' => $makeId,
            'model_id' => $modelId,
        ];
    }

    /**
     * Seed required lookup data for HealthQuote tests.
     *
     * @return array Array of created lookup IDs
     */
    public static function seedHealthQuoteLookups(): array
    {
        $db = DB::connection('sqlite');

        // Create Nationality
        $nationalityId = $db->table('nationality')->where('text', 'United Arab Emirates')->value('id');
        if (! $nationalityId) {
            $nationalityId = $db->table('nationality')->insertGetId([
                'text' => 'United Arab Emirates',
                'code' => 'AE',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [
            'nationality_id' => $nationalityId,
        ];
    }

    /**
     * Seed required lookup data for Group Medical (AMT) lead tests.
     * Ensures emirates and business_type_of_insurance (Group Medical) exist.
     *
     * @return array{emirate_of_registration_id: int, business_type_of_insurance_id: int}
     */
    public static function seedAmtGroupMedicalLookups(): array
    {
        $db = DB::connection('sqlite');

        $emirateId = $db->table('emirates')->where('text', 'Dubai')->value('id');
        if (! $emirateId) {
            $emirateId = $db->table('emirates')->insertGetId([
                'text' => 'Dubai',
                'code' => 'DXB',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $businessTypeId = $db->table('business_type_of_insurance')->where('text', 'Group Medical')->value('id');
        if (! $businessTypeId) {
            $businessTypeId = $db->table('business_type_of_insurance')->insertGetId([
                'text' => 'Group Medical',
                'code' => 'GM',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [
            'emirate_of_registration_id' => (int) $emirateId,
            'business_type_of_insurance_id' => (int) $businessTypeId,
        ];
    }

    /**
     * Seed a Savings DocumentType row on sqlite (e.g. `PP_SAV`).
     *
     * This is intentionally general-purpose for Savings. Add/override fields as new Savings OCR docs are introduced.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function seedSavingsDocumentType(string $code, string $text, array $overrides = []): void
    {
        $db = DB::connection('sqlite');

        $defaults = [
            'code' => $code,
            'text' => $text,
            'description' => '',
            'category' => 'QUOTE',
            'is_active' => 1,
            'quote_type_id' => QuoteTypes::SAVINGS->id(),
            'registration_type' => null,
            'vehicle_use' => null,
            'sort_order' => null,
            'receive_from_customer' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $db->table('document_types')->updateOrInsert(
            ['code' => $code],
            array_merge($defaults, $overrides),
        );
    }

    /**
     * Seed a DocumentType row by code/text/category for OCR tests.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function seedDocumentType(string $code, string $text, string $category = 'QUOTE', array $overrides = []): void
    {
        $db = DB::connection('sqlite');

        $defaults = [
            'code' => $code,
            'text' => $text,
            'description' => '',
            'category' => $category,
            'is_active' => 1,
            'receive_from_customer' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $db->table('document_types')->updateOrInsert(
            ['code' => $code],
            array_merge($defaults, $overrides),
        );
    }
}
