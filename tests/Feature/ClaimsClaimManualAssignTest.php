<?php

use App\Enums\RolesEnum;
use App\Models\ClaimRequest;
use App\Models\User;
use App\Services\ClaimsService;
use App\Services\ClaimStatusesService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->claimStatusesService = app(ClaimStatusesService::class);
    $this->claimsService = app(ClaimsService::class);

    $claimManagerRole = Role::firstOrCreate(
        ['name' => RolesEnum::ClaimsManager, 'guard_name' => 'web'],
        ['created_at' => now(), 'updated_at' => now()]
    );
    $this->claimManager = User::factory()->create([
        'name' => 'Claim Manager User',
        'email' => 'claim-manager-'.uniqid().'@test.com',
    ]);
    $this->claimManager->assignRole($claimManagerRole);

    $this->regularUser = User::factory()->create([
        'name' => 'Regular User',
        'email' => 'regular-'.uniqid().'@test.com',
    ]);

});

test('assignClaim throws ValidationException when user is not a claims manager', function () {
    $claim = new ClaimRequest;
    $claim->id = 1;
    $claim->uuid = Str::uuid()->toString();
    $claim->manager_id = null;
    $claim->exists = true;

    $this->claimsService->assignClaim($claim, $this->regularUser->id);
})->throws(ValidationException::class);

test('assignClaim throws ValidationException when manager id does not exist', function () {
    $claim = new ClaimRequest;
    $claim->id = 1;
    $claim->uuid = Str::uuid()->toString();
    $claim->manager_id = null;
    $claim->exists = true;

    $this->claimsService->assignClaim($claim, 999999);
})->throws(ValidationException::class);
