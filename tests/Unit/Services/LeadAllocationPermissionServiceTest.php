<?php

declare(strict_types=1);

use App\Enums\AuthGuardEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Services\LeadAllocationPermissionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
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

test('mutate permissions for business merges corpline and group medical', function () {
    $perms = LeadAllocationPermissionService::mutatePermissionsForQuoteType(QuoteTypes::BUSINESS);

    expect($perms)->toContain(PermissionsEnum::CORPLINE_LEAD_ALLOCATION_DASHBOARD)
        ->and($perms)->toContain(PermissionsEnum::CORPLINE_LEAD_ALLOCATION_EDIT)
        ->and($perms)->toContain(PermissionsEnum::GROUP_MEDICAL_LEAD_ALLOCATION_DASHBOARD)
        ->and($perms)->toContain(PermissionsEnum::GROUP_MEDICAL_LEAD_ALLOCATION_EDIT);
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

test('authorize mutate for business id allows user with corpline permission only', function () {
    $user = TestDataSeeder::createUser();
    Permission::findOrCreate(PermissionsEnum::CORPLINE_LEAD_ALLOCATION_EDIT, AuthGuardEnum::Web->value);
    $user->givePermissionTo(PermissionsEnum::CORPLINE_LEAD_ALLOCATION_EDIT);
    $this->actingAs($user);

    LeadAllocationPermissionService::authorizeMutateForQuoteTypeId(5);

    expect(true)->toBeTrue();
});

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

test('authorize mutate for route uses url segment when input quote type absent', function () {
    $user = TestDataSeeder::createUser();
    Permission::findOrCreate(PermissionsEnum::HEALTH_LEAD_ALLOCATION_EDIT, AuthGuardEnum::Web->value);
    $user->givePermissionTo(PermissionsEnum::HEALTH_LEAD_ALLOCATION_EDIT);
    $this->actingAs($user);

    $request = Request::create('http://localhost/test', 'POST', []);
    $route = new Route('POST', 'lead-allocation/{quoteType}/update-availability', []);
    $route->parameters = ['quoteType' => 'Health'];
    $request->setRouteResolver(fn () => $route);

    LeadAllocationPermissionService::authorizeMutateForRouteQuoteType($request);

    expect(true)->toBeTrue();
});

test('authorize mutate for route prefers input quote type over url when both present', function () {
    $user = TestDataSeeder::createUser();
    Permission::findOrCreate(PermissionsEnum::CAR_LEAD_ALLOCATION_EDIT, AuthGuardEnum::Web->value);
    $user->givePermissionTo(PermissionsEnum::CAR_LEAD_ALLOCATION_EDIT);
    $this->actingAs($user);

    $request = Request::create('http://localhost/test', 'POST', ['quoteType' => 'Car']);
    $route = new Route('POST', 'lead-allocation/{quoteType}/update-availability', []);
    $route->parameters = ['quoteType' => 'Health'];
    $request->setRouteResolver(fn () => $route);

    LeadAllocationPermissionService::authorizeMutateForRouteQuoteType($request);

    expect(true)->toBeTrue();
});

test('authorize mutate for route aborts for invalid quote type', function () {
    $user = TestDataSeeder::createUser();
    $this->actingAs($user);

    $request = Request::create('/test', 'POST', ['quoteType' => 'NotARealLob']);

    LeadAllocationPermissionService::authorizeMutateForRouteQuoteType($request);
})->throws(HttpException::class, 'Unauthorized action.');

test('authorize shared toggle lead or user allows when user can mutate for resolved quote type', function () {
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

    LeadAllocationPermissionService::authorizeMutateForSharedToggleLeadOrUser($request);

    expect(true)->toBeTrue();
});

test('authorize shared toggle lead or user aborts when user cannot mutate for resolved quote type', function () {
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

    LeadAllocationPermissionService::authorizeMutateForSharedToggleLeadOrUser($request);
})->throws(HttpException::class, 'Unauthorized action.');

test('authorize shared toggle lead or user aborts when lead id and user id are both absent', function () {
    $user = TestDataSeeder::createUser();
    Permission::findOrCreate(PermissionsEnum::CAR_LEAD_ALLOCATION_EDIT, AuthGuardEnum::Web->value);
    $user->givePermissionTo(PermissionsEnum::CAR_LEAD_ALLOCATION_EDIT);
    $this->actingAs($user);

    $request = Request::create('/test', 'POST', []);

    LeadAllocationPermissionService::authorizeMutateForSharedToggleLeadOrUser($request);
})->throws(HttpException::class, 'Unauthorized action.');

test('authorize shared toggle lead or user allows when only user id is present', function () {
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

    LeadAllocationPermissionService::authorizeMutateForSharedToggleLeadOrUser($request);

    expect(true)->toBeTrue();
});

test('authorize shared toggle lead or user uses lead id when both la id and lead id are present', function () {
    $actor = TestDataSeeder::createUser();
    Permission::findOrCreate(PermissionsEnum::CAR_LEAD_ALLOCATION_EDIT, AuthGuardEnum::Web->value);
    $actor->givePermissionTo(PermissionsEnum::CAR_LEAD_ALLOCATION_EDIT);
    $this->actingAs($actor);

    $carLaId = (int) DB::connection('sqlite')->table('lead_allocation')->insertGetId([
        'user_id' => $actor->id,
        'quote_type_id' => QuoteTypes::CAR->id(),
        'auto_assignment_count' => 0,
        'manual_assignment_count' => 0,
        'max_capacity' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $healthLeadId = (int) DB::connection('sqlite')->table('lead_allocation')->insertGetId([
        'user_id' => $actor->id,
        'quote_type_id' => QuoteTypes::HEALTH->id(),
        'auto_assignment_count' => 0,
        'manual_assignment_count' => 0,
        'max_capacity' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $request = Request::create('/test', 'POST', [
        'laId' => $carLaId,
        'leadId' => $healthLeadId,
    ]);

    LeadAllocationPermissionService::authorizeMutateForSharedToggleLeadOrUser($request);
})->throws(HttpException::class, 'Unauthorized action.');

test('authorize shared toggle la or user allows when only la id is present', function () {
    $user = TestDataSeeder::createUser();
    Permission::findOrCreate(PermissionsEnum::CAR_LEAD_ALLOCATION_EDIT, AuthGuardEnum::Web->value);
    $user->givePermissionTo(PermissionsEnum::CAR_LEAD_ALLOCATION_EDIT);
    $this->actingAs($user);

    $laId = (int) DB::connection('sqlite')->table('lead_allocation')->insertGetId([
        'user_id' => $user->id,
        'quote_type_id' => QuoteTypes::CAR->id(),
        'auto_assignment_count' => 0,
        'manual_assignment_count' => 0,
        'max_capacity' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $request = Request::create('/test', 'POST', ['laId' => $laId]);

    LeadAllocationPermissionService::authorizeMutateForSharedToggleLaOrUser($request);

    expect(true)->toBeTrue();
});

test('authorize shared toggle la or user uses la id when both la id and lead id are present', function () {
    $actor = TestDataSeeder::createUser();
    Permission::findOrCreate(PermissionsEnum::CAR_LEAD_ALLOCATION_EDIT, AuthGuardEnum::Web->value);
    $actor->givePermissionTo(PermissionsEnum::CAR_LEAD_ALLOCATION_EDIT);
    $this->actingAs($actor);

    $carLaId = (int) DB::connection('sqlite')->table('lead_allocation')->insertGetId([
        'user_id' => $actor->id,
        'quote_type_id' => QuoteTypes::CAR->id(),
        'auto_assignment_count' => 0,
        'manual_assignment_count' => 0,
        'max_capacity' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $healthLaId = (int) DB::connection('sqlite')->table('lead_allocation')->insertGetId([
        'user_id' => $actor->id,
        'quote_type_id' => QuoteTypes::HEALTH->id(),
        'auto_assignment_count' => 0,
        'manual_assignment_count' => 0,
        'max_capacity' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $request = Request::create('/test', 'POST', [
        'laId' => $carLaId,
        'leadId' => $healthLaId,
    ]);

    LeadAllocationPermissionService::authorizeMutateForSharedToggleLaOrUser($request);

    expect(true)->toBeTrue();
});
