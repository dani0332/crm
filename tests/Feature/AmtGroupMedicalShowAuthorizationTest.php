<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

test('group medical amt show returns forbidden for users without gm access regardless of uuid', function () {
    $user = TestDataSeeder::createUserWithRole(RolesEnum::BusinessAdvisor);

    $this->actingAs($user);

    $response = $this->get(route('amt.show', 'ffffffff-ffff-ffff-ffff-ffffffffffff'));

    $response->assertForbidden();
});
