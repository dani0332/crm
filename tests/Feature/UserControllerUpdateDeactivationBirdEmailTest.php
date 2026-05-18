<?php

use App\Enums\ApplicationStorageEnums;
use App\Enums\RolesEnum;
use App\Http\Middleware\PreventRequestForgery;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    $this->admin = TestDataSeeder::createAdminUser(['email' => fake()->unique()->safeEmail()]);

    // Prevent model event listeners from causing unrelated test failures.
    Event::fake();

    $this->actingAs($this->admin);
    // Keep full route middleware (auth/last_login_check/check_route_access) enabled; skip CSRF only.
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->workflowUrl = 'https://example.test/bird/manager-deactivation-workflow';
    $this->itSupportEmail = 'it-support-'.uniqid().'@example.test';

    TestDataSeeder::seedApplicationStorage([
        ApplicationStorageEnums::BIRD_MANAGER_DEACTIVATION_ATTEMPT_WORKFLOW => $this->workflowUrl,
        ApplicationStorageEnums::IT_SUPPORT_EMAIL => $this->itSupportEmail,
    ]);

    ['productTeamId' => $this->productTeamId, 'teamId' => $this->teamId] = TestDataSeeder::seedTeamHierarchy();
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

    $response = $this->put(route('users.update', $manager), [
        'name' => $manager->name,
        'email' => $manager->email,
        'roles' => [RolesEnum::Admin],
        'teams' => [$this->teamId],
        'manager' => '0',
        'rm_category_id' => -1,
        'is_active' => 0,
    ]);

    $response->assertRedirect(route('users.show', $manager->id));
    $response->assertSessionHas('success');

    $manager->refresh();
    expect((int) $manager->is_active)->toBe(0);

    // Verify job was queued - much faster than manually firing it
    $jobsCount = DB::connection('sqlite')->table('jobs')->count();
    expect($jobsCount)->toBe(1);

    $payload = (string) DB::connection('sqlite')->table('jobs')->value('payload');
    expect($payload)->toContain('SendManagerDeactivationAttemptEmailJob');
    expect($payload)->toContain((string) $manager->id);
});

test('deactivating a user with no subordinates does not send Bird email', function () {
    $user = TestDataSeeder::createUser([
        'email' => fake()->unique()->safeEmail(),
        'is_active' => 1,
    ]);

    $response = $this->put(route('users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'roles' => [RolesEnum::Admin],
        'teams' => [$this->teamId],
        'manager' => '0',
        'rm_category_id' => -1,
        'is_active' => 0,
    ]);

    $response->assertRedirect(route('users.show', $user->id));
    $response->assertSessionHas('success');

    $user->refresh();
    expect((int) $user->is_active)->toBe(0);

    // No subordinates => controller skips dispatch (no job should be queued).
    expect(DB::connection('sqlite')->table('jobs')->count())->toBe(0);
});
