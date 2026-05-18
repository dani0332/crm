<?php

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    // Ensure default database connection is sqlite for tests
    config(['database.default' => 'sqlite']);
    DB::setDefaultConnection('sqlite');

    // Override mysql connection to use the same sqlite database
    // This handles models like InsuranceProvider that have protected $connection = 'mysql'
    // Both connections will use the same :memory: database
    $sqliteConfig = config('database.connections.sqlite');
    config([
        'database.connections.mysql.driver' => 'sqlite',
        'database.connections.mysql.database' => $sqliteConfig['database'] ?? ':memory:',
        'database.connections.mysql.prefix' => $sqliteConfig['prefix'] ?? '',
    ]);
    DB::purge('mysql');
    DB::reconnect('mysql');

    // Set required config values for date formatting
    config(['constants.DB_DATE_FORMAT_MATCH' => 'Y-m-d H:i:s']);
    config(['constants.DATE_FORMAT_ONLY' => 'Y-m-d']);
    config(['constants.DATE_DISPLAY_FORMAT' => 'd-M-Y']);

    // Set CAPI config values to prevent HTTP calls
    config(['constants.CENTRAL_API_ENDPOINT' => 'http://api']);
    config(['constants.CENTRAL_API_USER' => 'test']);
    config(['constants.CENTRAL_API_PWD' => 'test']);
    config(['constants.CENTRAL_API_TOKEN' => 'test-token']);
    config(['constants.CENTRAL_API_TIMEOUT' => 30]);

    TestSchemaCreator::createMinimalSchema();

    // Ensure activity_log table exists on mysql connection as well
    // (ActivityLog model uses mysql connection, but schema is only created on sqlite)
    if (! Schema::connection('mysql')->hasTable('activity_log')) {
        Schema::connection('mysql')->create('activity_log', function ($table) {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description')->nullable();
            $table->string('url')->nullable();
            $table->string('feature')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('subject_type')->nullable();
            $table->string('event')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('causer_type')->nullable();
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->json('attribute_changes')->nullable();
            $table->text('properties')->nullable();
            $table->string('batch_uuid')->nullable();
            $table->string('code')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    // Set up minimal ApplicationStorage data
    $db = DB::connection('sqlite');
    $db->table('application_storage')->insertOrIgnore([
        'key_name' => 'PAYMENT_AUTHORISED_DAYS',
        'value' => '30',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Seed quote_type table with required quote types
    $quoteTypes = [
        ['id' => 1, 'code' => 'Car', 'text' => 'Car Insurance', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'code' => 'Home', 'text' => 'Home Insurance', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 3, 'code' => 'Health', 'text' => 'Health Insurance', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 4, 'code' => 'Life', 'text' => 'Life Insurance', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 5, 'code' => 'Business', 'text' => 'Business Insurance', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 6, 'code' => 'Bike', 'text' => 'Bike Insurance', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 7, 'code' => 'Yacht', 'text' => 'Yacht Insurance', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 8, 'code' => 'Travel', 'text' => 'Travel Insurance', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 9, 'code' => 'Pet', 'text' => 'Pet Insurance', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 10, 'code' => 'Cycle', 'text' => 'Cycle Insurance', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 11, 'code' => 'Jetski', 'text' => 'Jetski Insurance', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 18, 'code' => 'Savings', 'text' => 'Savings Insurance', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
    ];

    foreach ($quoteTypes as $quoteType) {
        $db->table('quote_type')->insertOrIgnore($quoteType);
    }

    // Seed quote_status table with minimal statuses
    $quoteStatuses = [
        ['id' => 1, 'code' => 'Draft', 'text' => 'Draft', 'is_active' => 1, 'sort_order' => 1, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'code' => 'Quoted', 'text' => 'Quoted', 'is_active' => 1, 'sort_order' => 2, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 9, 'code' => 'Lost', 'text' => 'Lost', 'is_active' => 1, 'sort_order' => 9, 'is_deleted' => 0, 'created_at' => now(), 'updated_at' => now()],
    ];

    foreach ($quoteStatuses as $status) {
        $db->table('quote_status')->insertOrIgnore($status);
    }

    // Seed quote_status_map for Pet quote type (id = 9)
    $quoteStatusMaps = [
        ['quote_status_id' => 1, 'quote_type_id' => 9, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['quote_status_id' => 2, 'quote_type_id' => 9, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
    ];

    foreach ($quoteStatusMaps as $map) {
        $db->table('quote_status_map')->insertOrIgnore($map);
    }

    // Override SQLite grammar to handle CONCAT by replacing it with SQLite's || operator
    $connection = DB::connection('sqlite');
    $grammar = new class($connection) extends SQLiteGrammar
    {
        public function compileSelect(Builder $query)
        {
            $sql = parent::compileSelect($query);
            // Replace CONCAT with SQLite's || operator
            if (strpos($sql, 'CONCAT') !== false) {
                $sql = preg_replace_callback(
                    "/CONCAT\s*\(([^)]+)\)/i",
                    function ($matches) {
                        // Split CONCAT arguments and join with ||
                        $args = preg_split('/\s*,\s*/', trim($matches[1]));

                        return '('.implode(' || ', $args).')';
                    },
                    $sql
                );
            }

            return $sql;
        }
    };
    $connection->setQueryGrammar($grammar);
});

/**
 * Seed required roles and teams for testing
 */
function seedRolesAndTeams(): int
{
    $db = DB::connection('sqlite');

    // Create roles
    $roles = [
        'ADMIN',
        'PET_ADVISOR', 'PET_RENEWAL_ADVISOR', 'PET_NEW_BUSINESS_ADVISOR',
        'BIKE_ADVISOR',
        'CYCLE_ADVISOR',
        'YACHT_ADVISOR',
        'JETSKI_ADVISOR',
        'LIFE_ADVISOR',
        'HOME_ADVISOR',
        'CAR_ADVISOR', 'CAR_DEPUTY_MANAGER',
        'SAVINGS_ADVISOR', 'SAVINGS_MANAGER',
        'TRAVEL_ADVISOR',
        'HEALTH_ADVISOR',
        // Health quotes require these roles for CRUDService::getAdvisorsByModelType
        'RM_ADVISOR',
        'EBP_ADVISOR',
        'HEALTH_RENEWAL_ADVISOR',
    ];

    foreach ($roles as $roleName) {
        $db->table('roles')->insertOrIgnore([
            'name' => $roleName,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Create a team for CRUDService tests
    $teamId = $db->table('teams')->insertGetId([
        'name' => 'Test Team',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $teamId;
}

/**
 * Grant permission to a user
 */
function grantPermissionToUser(User $user, string $permissionName): void
{
    $db = DB::connection('sqlite');

    // Get or create permission
    $permissionId = $db->table('permissions')->where('name', $permissionName)->where('guard_name', 'web')->value('id');
    if (! $permissionId) {
        $permissionId = $db->table('permissions')->insertGetId([
            'name' => $permissionName,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Grant permission to user
    $db->table('model_has_permissions')->insertOrIgnore([
        'permission_id' => $permissionId,
        'model_type' => User::class,
        'model_id' => $user->id,
    ]);
}

/**
 * Create a user with a specific role and active status
 */
function createUserWithRole(string $roleName, bool $isActive = true, ?int $teamId = null): User
{
    $user = User::factory()->create([
        'is_active' => $isActive ? 1 : 0,
    ]);
    $user->setConnection('sqlite');

    $db = DB::connection('sqlite');
    $roleId = $db->table('roles')->where('name', $roleName)->value('id');

    if ($roleId) {
        $db->table('model_has_roles')->insertOrIgnore([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);
    }

    // For CRUDService tests, assign user to team
    if ($teamId) {
        $db->table('user_team')->insertOrIgnore([
            'user_id' => $user->id,
            'team_id' => $teamId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    return $user;
}

test('pet quotes index returns only active advisors', function () {
    $teamId = seedRolesAndTeams();
    $admin = TestDataSeeder::createUserWithRole(RolesEnum::Admin);
    $activeAdvisor = createUserWithRole('PET_ADVISOR', true, $teamId);
    $inactiveAdvisor = createUserWithRole('PET_ADVISOR', false, $teamId);

    $response = $this->actingAs($admin)
        ->get(route('pet-quotes-list'));

    $response->assertStatus(200);

    // Verify advisors array contains only active users
    $response->assertInertia(fn ($page) => $page
        ->has('advisors')
        ->where('advisors', function ($advisors) use ($activeAdvisor, $inactiveAdvisor) {
            if (! is_array($advisors) && ! ($advisors instanceof Collection)) {
                return false;
            }
            $advisorIds = collect($advisors)->pluck('id')->toArray();

            return in_array($activeAdvisor->id, $advisorIds) &&
                   ! in_array($inactiveAdvisor->id, $advisorIds);
        })
    );
});

test('bike quotes index returns only active advisors', function () {
    $teamId = seedRolesAndTeams();
    $admin = TestDataSeeder::createUserWithRole(RolesEnum::Admin);
    $activeAdvisor = createUserWithRole('BIKE_ADVISOR', true, $teamId);
    $inactiveAdvisor = createUserWithRole('BIKE_ADVISOR', false, $teamId);

    $response = $this->actingAs($admin)
        ->get(route('bike-quotes-list'));

    $response->assertStatus(200);

    // Verify advisors array contains only active users
    $response->assertInertia(fn ($page) => $page
        ->has('advisors')
        ->where('advisors', function ($advisors) use ($activeAdvisor, $inactiveAdvisor) {
            if (! is_array($advisors) && ! ($advisors instanceof Collection)) {
                return false;
            }
            $advisorIds = collect($advisors)->pluck('id')->toArray();

            return in_array($activeAdvisor->id, $advisorIds) &&
                   ! in_array($inactiveAdvisor->id, $advisorIds);
        })
    );
});

test('cycle quotes index returns only active advisors', function () {
    $teamId = seedRolesAndTeams();
    $admin = TestDataSeeder::createUserWithRole(RolesEnum::Admin);
    $activeAdvisor = createUserWithRole('CYCLE_ADVISOR', true, $teamId);
    $inactiveAdvisor = createUserWithRole('CYCLE_ADVISOR', false, $teamId);

    $response = $this->actingAs($admin)
        ->get(route('cycle-quotes-list'));

    $response->assertStatus(200);

    // Verify advisors array contains only active users
    $response->assertInertia(fn ($page) => $page
        ->has('advisors')
        ->where('advisors', function ($advisors) use ($activeAdvisor, $inactiveAdvisor) {
            if (! is_array($advisors) && ! ($advisors instanceof Collection)) {
                return false;
            }
            $advisorIds = collect($advisors)->pluck('id')->toArray();

            return in_array($activeAdvisor->id, $advisorIds) &&
                   ! in_array($inactiveAdvisor->id, $advisorIds);
        })
    );
});

test('yacht quotes index returns only active advisors', function () {
    $teamId = seedRolesAndTeams();
    $admin = TestDataSeeder::createUserWithRole(RolesEnum::Admin);
    $activeAdvisor = createUserWithRole('YACHT_ADVISOR', true, $teamId);
    $inactiveAdvisor = createUserWithRole('YACHT_ADVISOR', false, $teamId);

    $response = $this->actingAs($admin)
        ->get(route('yacht-quotes-list'));

    $response->assertStatus(200);

    // Verify advisors array contains only active users
    $response->assertInertia(fn ($page) => $page
        ->has('advisors')
        ->where('advisors', function ($advisors) use ($activeAdvisor, $inactiveAdvisor) {
            if (! is_array($advisors) && ! ($advisors instanceof Collection)) {
                return false;
            }
            $advisorIds = collect($advisors)->pluck('id')->toArray();

            return in_array($activeAdvisor->id, $advisorIds) &&
                   ! in_array($inactiveAdvisor->id, $advisorIds);
        })
    );
});

test('jetski quotes index returns only active advisors', function () {
    $teamId = seedRolesAndTeams();
    $admin = TestDataSeeder::createUserWithRole(RolesEnum::Admin);
    $activeAdvisor = createUserWithRole('JETSKI_ADVISOR', true, $teamId);
    $inactiveAdvisor = createUserWithRole('JETSKI_ADVISOR', false, $teamId);

    $response = $this->actingAs($admin)
        ->get(route('jetski-quotes-list'));

    $response->assertStatus(200);

    // Verify advisors array contains only active users
    $response->assertInertia(fn ($page) => $page
        ->has('advisors')
        ->where('advisors', function ($advisors) use ($activeAdvisor, $inactiveAdvisor) {
            if (! is_array($advisors) && ! ($advisors instanceof Collection)) {
                return false;
            }
            $advisorIds = collect($advisors)->pluck('id')->toArray();

            return in_array($activeAdvisor->id, $advisorIds) &&
                   ! in_array($inactiveAdvisor->id, $advisorIds);
        })
    );
});

test('life quotes index returns only active advisors', function () {
    $teamId = seedRolesAndTeams();
    $admin = TestDataSeeder::createUserWithRole(RolesEnum::Admin);
    $activeAdvisor = createUserWithRole('LIFE_ADVISOR', true, $teamId);
    $inactiveAdvisor = createUserWithRole('LIFE_ADVISOR', false, $teamId);

    $response = $this->actingAs($admin)
        ->get(route('life-quotes-list'));

    $response->assertStatus(200);

    // Verify advisors array contains only active users
    $response->assertInertia(fn ($page) => $page
        ->has('advisors')
        ->where('advisors', function ($advisors) use ($activeAdvisor, $inactiveAdvisor) {
            if (! is_array($advisors) && ! ($advisors instanceof Collection)) {
                return false;
            }
            $advisorIds = collect($advisors)->pluck('id')->toArray();

            return in_array($activeAdvisor->id, $advisorIds) &&
                   ! in_array($inactiveAdvisor->id, $advisorIds);
        })
    );
});

test('home quotes index returns only active advisors', function () {
    $teamId = seedRolesAndTeams();
    $admin = TestDataSeeder::createUserWithRole(RolesEnum::Admin);
    $activeAdvisor = createUserWithRole('HOME_ADVISOR', true, $teamId);
    $inactiveAdvisor = createUserWithRole('HOME_ADVISOR', false, $teamId);

    $response = $this->actingAs($admin)
        ->get(route('home-quotes-list'));

    $response->assertStatus(200);

    // Verify advisors array contains only active users
    $response->assertInertia(fn ($page) => $page
        ->has('advisors')
        ->where('advisors', function ($advisors) use ($activeAdvisor, $inactiveAdvisor) {
            if (! is_array($advisors) && ! ($advisors instanceof Collection)) {
                return false;
            }
            $advisorIds = collect($advisors)->pluck('id')->toArray();

            return in_array($activeAdvisor->id, $advisorIds) &&
                   ! in_array($inactiveAdvisor->id, $advisorIds);
        })
    );
});

test('car quotes index returns only active advisors', function () {
    $teamId = seedRolesAndTeams();
    $admin = TestDataSeeder::createUserWithRole(RolesEnum::Admin);
    $activeAdvisor = createUserWithRole('CAR_ADVISOR', true, $teamId);
    $inactiveAdvisor = createUserWithRole('CAR_ADVISOR', false, $teamId);

    $response = $this->actingAs($admin)
        ->get(route('car-quotes-search'));

    $response->assertStatus(200);

    // Verify advisors array contains only active users
    $response->assertInertia(fn ($page) => $page
        ->has('advisors')
        ->where('advisors', function ($advisors) use ($activeAdvisor, $inactiveAdvisor) {
            if (! is_array($advisors) && ! ($advisors instanceof Collection)) {
                return false;
            }
            $advisorIds = collect($advisors)->pluck('id')->toArray();

            return in_array($activeAdvisor->id, $advisorIds) &&
                   ! in_array($inactiveAdvisor->id, $advisorIds);
        })
    );
});

test('savings quotes index returns only active advisors', function () {
    // Mock the Capi facade for savings lookups
    $mockCapi = Mockery::mock('alias:App\Facades\Capi');
    $mockCapi->shouldReceive('request')
        ->andReturn((object) [
            'savingsInvestmentType' => [],
        ]);

    $teamId = seedRolesAndTeams();
    $admin = TestDataSeeder::createUserWithRole(RolesEnum::Admin);
    grantPermissionToUser($admin, PermissionsEnum::SAVINGS_QUOTES_LIST);
    $activeAdvisor = createUserWithRole('SAVINGS_ADVISOR', true, $teamId);
    $inactiveAdvisor = createUserWithRole('SAVINGS_ADVISOR', false, $teamId);

    $response = $this->actingAs($admin)
        ->get(route('savings-quotes-list'));

    $response->assertStatus(200);

    // Verify advisors array contains only active users
    $response->assertInertia(fn ($page) => $page
        ->has('advisors')
        ->where('advisors', function ($advisors) use ($activeAdvisor, $inactiveAdvisor) {
            if (! is_array($advisors) && ! ($advisors instanceof Collection)) {
                return false;
            }
            $advisorIds = collect($advisors)->pluck('id')->toArray();

            return in_array($activeAdvisor->id, $advisorIds) &&
                   ! in_array($inactiveAdvisor->id, $advisorIds);
        })
    );
});

test('travel quotes index returns only active advisors via CRUDService', function () {
    $teamId = seedRolesAndTeams();
    $admin = TestDataSeeder::createUserWithRole(RolesEnum::Admin);
    grantPermissionToUser($admin, PermissionsEnum::TravelQuotesList);
    $activeAdvisor = createUserWithRole('TRAVEL_ADVISOR', true, $teamId);
    $inactiveAdvisor = createUserWithRole('TRAVEL_ADVISOR', false, $teamId);

    $response = $this->actingAs($admin)
        ->get('/quotes/travel');

    $response->assertStatus(200);

    // Verify advisors array contains only active users
    $response->assertInertia(fn ($page) => $page
        ->has('advisors')
        ->where('advisors', function ($advisors) use ($activeAdvisor, $inactiveAdvisor) {
            if (! is_array($advisors) && ! ($advisors instanceof Collection)) {
                return false;
            }
            $advisorIds = collect($advisors)->pluck('id')->toArray();

            return in_array($activeAdvisor->id, $advisorIds) &&
                   ! in_array($inactiveAdvisor->id, $advisorIds);
        })
    );
});

test('health quotes index returns only active advisors via CRUDService', function () {
    $teamId = seedRolesAndTeams();
    $admin = TestDataSeeder::createUserWithRole(RolesEnum::Admin);
    grantPermissionToUser($admin, PermissionsEnum::HealthQuotesList);
    // Health quotes use RM_ADVISOR, EBP_ADVISOR, or HEALTH_RENEWAL_ADVISOR (not HEALTH_ADVISOR)
    $activeAdvisor = createUserWithRole('RM_ADVISOR', true, $teamId);
    $inactiveAdvisor = createUserWithRole('RM_ADVISOR', false, $teamId);

    $response = $this->actingAs($admin)
        ->get('/quotes/health');

    $response->assertStatus(200);

    // Verify advisors array contains only active users
    $response->assertInertia(fn ($page) => $page
        ->has('advisors')
        ->where('advisors', function ($advisors) use ($activeAdvisor, $inactiveAdvisor) {
            if (! is_array($advisors) && ! ($advisors instanceof Collection)) {
                return false;
            }
            $advisorIds = collect($advisors)->pluck('id')->toArray();

            return in_array($activeAdvisor->id, $advisorIds) &&
                   ! in_array($inactiveAdvisor->id, $advisorIds);
        })
    );
});
