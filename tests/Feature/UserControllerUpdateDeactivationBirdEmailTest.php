<?php

use App\Enums\RolesEnum;
use App\Http\Middleware\VerifyCsrfToken;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Helpers\BirdMockHelper;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    $this->admin = TestDataSeeder::createAdminUser(['email' => fake()->unique()->safeEmail()]);

    // Prevent model event listeners from causing unrelated test failures.
    Event::fake();

    $this->actingAs($this->admin);
    // Keep full route middleware (auth/last_login_check/check_route_access) enabled; skip CSRF only.
    $this->withoutMiddleware(VerifyCsrfToken::class);


    $this->workflowUrl = 'https://example.test/bird/manager-deactivation-workflow';
    $this->itSupportEmail = 'it-support-' . uniqid() . '@example.test';

    TestDataSeeder::seedApplicationStorage([
        \App\Enums\ApplicationStorageEnums::BIRD_MANAGER_DEACTIVATION_ATTEMPT_WORKFLOW => $this->workflowUrl,
        \App\Enums\ApplicationStorageEnums::IT_SUPPORT_EMAIL => $this->itSupportEmail,
    ]);

    ['productTeamId' => $this->productTeamId, 'teamId' => $this->teamId] = TestDataSeeder::seedTeamHierarchy();
});

afterEach(function () {
    Mockery::close();
});

test('deactivating a manager with subordinates sends Bird email', function () {
    $manager = TestDataSeeder::createUser([
        'email' => fake()->unique()->safeEmail(),
        'is_active' => 1,
    ]);

    $subordinate = TestDataSeeder::createUser([
        'email' => fake()->unique()->safeEmail(),
        'is_active' => 1,
    ]);

    $subordinate->managers()->attach($manager->id);

    // Debug guard: if this is empty, the controller will skip dispatch and `jobs` will stay 0.
    $subordinates = app(UserService::class)->getSubordinates($manager->id);
    expect($subordinates)->not->toBeEmpty();

    BirdMockHelper::expectManagerDeactivationAttemptEmailSent($this->workflowUrl, $manager->id);

    $response = $this->put(route('users.update', $manager), [
        'name' => $manager->name,
        'email' => $manager->email,
        'roles' => [RolesEnum::Admin],
        'teams' => [$this->teamId],
        'manager' => '0',
        'rm_category_id' => -1,
        'is_active' => 0,
    ]);


    if (session()->has('errors')) {
        throw new RuntimeException(json_encode([
            'label' => 'manager-with-subordinates',
            'default_connection' => config('database.default'),
            'manager_connection' => $manager->getConnectionName(),
            'manager_query_connection' => $manager->newQuery()->getConnection()->getName(),
            'manager_id' => $manager->id,
            'manager_email' => $manager->email,
            'sqlite_users_count' => DB::connection('sqlite')->table('users')->count(),
            'sqlite_jobs_count' => DB::connection('sqlite')->table('jobs')->count(),
            'session_errors' => session('errors')?->toArray() ?? null,
        ], JSON_PRETTY_PRINT));
    }

    $response->assertRedirect(route('users.show', $manager->id));
    $response->assertSessionHas('success');

    $manager->refresh();
    expect((int)$manager->is_active)->toBe(0);

    // 2) Job should be enlisted in sqlite in-memory `jobs` table (queued after DB::afterCommit).
    $jobsCount = DB::connection('sqlite')->table('jobs')->count();
    expect($jobsCount)->toBe(1);

    $payload = (string)DB::connection('sqlite')->table('jobs')->value('payload');
    expect($payload)->toContain('SendManagerDeactivationAttemptEmailJob');

    // 3) Process the queued job to assert BirdRequest was successful via the mock expectation.
    $job = app('queue')->connection()->pop();
    expect($job)->not->toBeNull();
    $job->fire();
    $job->delete();

    expect(DB::connection('sqlite')->table('jobs')->count())->toBe(0);
});

test('deactivating a user with no subordinates does not send Bird email', function () {
    $user = TestDataSeeder::createUser([
        'email' => fake()->unique()->safeEmail(),
        'is_active' => 1,
    ]);

    BirdMockHelper::expectNoBirdCalls();

    $response = $this->put(route('users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'roles' => [RolesEnum::Admin],
        'teams' => [$this->teamId],
        'manager' => '0',
        'rm_category_id' => -1,
        'is_active' => 0,
    ]);

    if (session()->has('errors')) {
        throw new RuntimeException(json_encode([
            'label' => 'user-without-subordinates',
            'default_connection' => config('database.default'),
            'user_connection' => $user->getConnectionName(),
            'user_query_connection' => $user->newQuery()->getConnection()->getName(),
            'user_id' => $user->id,
            'user_email' => $user->email,
            'sqlite_users_count' => DB::connection('sqlite')->table('users')->count(),
            'session_errors' => session('errors')?->toArray() ?? null,
        ], JSON_PRETTY_PRINT));
    }

    $response->assertRedirect(route('users.show', $user->id));
    $response->assertSessionHas('success');

    $user->refresh();
    expect((int)$user->is_active)->toBe(0);

    // No subordinates => controller skips dispatch (no DB::afterCommit job).
    expect(DB::connection('sqlite')->table('jobs')->count())->toBe(0);
});
