<?php

declare(strict_types=1);

use App\Enums\PermissionsEnum;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

test('guests cannot access the PQA lead allocation dashboard', function () {
    $this->get(route('pqa-lead-allocation-dashboard'))
        ->assertRedirect();
});

test('users without PQA permissions cannot access the dashboard', function () {
    $user = TestDataSeeder::createUser();
    $this->actingAs($user);

    $this->get(route('pqa-lead-allocation-dashboard'))
        ->assertForbidden();
});

test('authorised users can access the PQA lead allocation dashboard', function () {
    $user = TestDataSeeder::createAdminUser([], [
        PermissionsEnum::PQA_LEAD_ALLOCATION_DASHBOARD,
    ]);

    $this->actingAs($user);

    $this->get(route('pqa-lead-allocation-dashboard'))
        ->assertSuccessful();
});
