<?php

declare(strict_types=1);

use App\Enums\AuthGuardEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Services\LeadAllocationPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

afterEach(function () {
    Mockery::close();
});

test('mutate permissions for null quote type is empty', function () {
    expect(LeadAllocationPermissionService::mutatePermissionsForQuoteType(null))->toBe([]);
});

test('mutate permissions for car includes dashboard and edit', function () {
    $perms = LeadAllocationPermissionService::mutatePermissionsForQuoteType(QuoteTypes::CAR);

    expect($perms)->toContain(PermissionsEnum::CAR_LEAD_ALLOCATION_DASHBOARD)
        ->and($perms)->toContain(PermissionsEnum::CAR_LEAD_ALLOCATION_EDIT);
});

test('mutate permissions for travel includes sic allocation', function () {
    $perms = LeadAllocationPermissionService::mutatePermissionsForQuoteType(QuoteTypes::TRAVEL);

    expect($perms)->toContain(PermissionsEnum::TRAVEL_SIC_ALLOCATION)
        ->and($perms)->toContain(PermissionsEnum::TRAVEL_LEAD_ALLOCATION_DASHBOARD)
        ->and($perms)->toContain(PermissionsEnum::TRAVEL_LEAD_ALLOCATION_EDIT);
});

test('mutate permissions for unknown product returns empty', function () {
    expect(LeadAllocationPermissionService::mutatePermissionsForQuoteType(QuoteTypes::JETSKI))
        ->toBe([]);
});

test('authorize mutate aborts when user has no lead allocation permissions', function () {
    $user = TestDataSeeder::createUser();
    $this->actingAs($user);

    LeadAllocationPermissionService::authorizeMutateForQuoteType(QuoteTypes::HEALTH);
})->throws(HttpException::class, 'Unauthorized action.');

test('authorize mutate allows user with health dashboard permission', function () {
    $user = TestDataSeeder::createUser();
    Permission::findOrCreate(PermissionsEnum::HEALTH_LEAD_ALLOCATION_DASHBOARD, AuthGuardEnum::Web->value);
    $user->givePermissionTo(PermissionsEnum::HEALTH_LEAD_ALLOCATION_DASHBOARD);
    $this->actingAs($user);

    LeadAllocationPermissionService::authorizeMutateForQuoteType(QuoteTypes::HEALTH);

    expect(true)->toBeTrue();
});

test('authorize mutate allows user with travel sic permission only', function () {
    $user = TestDataSeeder::createUser();
    Permission::findOrCreate(PermissionsEnum::TRAVEL_SIC_ALLOCATION, AuthGuardEnum::Web->value);
    $user->givePermissionTo(PermissionsEnum::TRAVEL_SIC_ALLOCATION);
    $this->actingAs($user);

    LeadAllocationPermissionService::authorizeMutateForQuoteType(QuoteTypes::TRAVEL);

    expect(true)->toBeTrue();
});

test('authorize mutate for route uses request quote type', function () {
    $user = TestDataSeeder::createUser();
    Permission::findOrCreate(PermissionsEnum::HEALTH_LEAD_ALLOCATION_EDIT, AuthGuardEnum::Web->value);
    $user->givePermissionTo(PermissionsEnum::HEALTH_LEAD_ALLOCATION_EDIT);
    $this->actingAs($user);

    $request = Request::create('/test', 'POST', ['quoteType' => 'Health']);

    LeadAllocationPermissionService::authorizeMutateForRouteQuoteType($request);

    expect(true)->toBeTrue();
});

test('authorize mutate for route aborts for invalid quote type', function () {
    $user = TestDataSeeder::createUser();
    $this->actingAs($user);

    $request = Request::create('/test', 'POST', ['quoteType' => 'NotARealLob']);

    LeadAllocationPermissionService::authorizeMutateForRouteQuoteType($request);
})->throws(HttpException::class, 'Unauthorized action.');

test('authorize shared toggle allows when user can mutate for resolved quote type', function () {
    $user = TestDataSeeder::createUser();
    Permission::findOrCreate(PermissionsEnum::CAR_LEAD_ALLOCATION_EDIT, AuthGuardEnum::Web->value);
    $user->givePermissionTo(PermissionsEnum::CAR_LEAD_ALLOCATION_EDIT);
    $this->actingAs($user);

    $leadId = (int) DB::connection('sqlite')->table('lead_allocation')->insertGetId([
        'user_id' => $user->id,
        'quote_type_id' => QuoteTypes::CAR->id(),
        'auto_assignment_count' => 0,
        'manual_assignment_count' => 0,
        'max_capacity' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $request = Request::create('/test', 'POST', ['leadId' => $leadId]);

    LeadAllocationPermissionService::authorizeMutateForSharedToggleRequest($request);

    expect(true)->toBeTrue();
});

test('authorize shared toggle aborts when user cannot mutate for resolved quote type', function () {
    $user = TestDataSeeder::createUser();
    $this->actingAs($user);

    $leadId = (int) DB::connection('sqlite')->table('lead_allocation')->insertGetId([
        'user_id' => $user->id,
        'quote_type_id' => QuoteTypes::CAR->id(),
        'auto_assignment_count' => 0,
        'manual_assignment_count' => 0,
        'max_capacity' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $request = Request::create('/test', 'POST', ['leadId' => $leadId]);

    LeadAllocationPermissionService::authorizeMutateForSharedToggleRequest($request);
})->throws(HttpException::class, 'Unauthorized action.');

test('authorize shared toggle aborts when lead id la id and user id are all absent', function () {
    $user = TestDataSeeder::createUser();
    Permission::findOrCreate(PermissionsEnum::CAR_LEAD_ALLOCATION_EDIT, AuthGuardEnum::Web->value);
    $user->givePermissionTo(PermissionsEnum::CAR_LEAD_ALLOCATION_EDIT);
    $this->actingAs($user);

    $request = Request::create('/test', 'POST', []);

    LeadAllocationPermissionService::authorizeMutateForSharedToggleRequest($request);
})->throws(HttpException::class, 'Unauthorized action.');

test('authorize shared toggle allows when only user id is present', function () {
    $actor = TestDataSeeder::createUser(['email' => 'manager@example.com']);
    Permission::findOrCreate(PermissionsEnum::CAR_LEAD_ALLOCATION_EDIT, AuthGuardEnum::Web->value);
    $actor->givePermissionTo(PermissionsEnum::CAR_LEAD_ALLOCATION_EDIT);
    $this->actingAs($actor);

    $advisor = TestDataSeeder::createUser(['email' => 'advisor@example.com']);

    DB::connection('sqlite')->table('lead_allocation')->insertGetId([
        'user_id' => $advisor->id,
        'quote_type_id' => QuoteTypes::CAR->id(),
        'auto_assignment_count' => 0,
        'manual_assignment_count' => 0,
        'max_capacity' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $request = Request::create('/test', 'POST', ['userId' => $advisor->id]);

    LeadAllocationPermissionService::authorizeMutateForSharedToggleRequest($request);

    expect(true)->toBeTrue();
});
