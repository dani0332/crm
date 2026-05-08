<?php

declare(strict_types=1);

use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

it('returns validation error when quote fields are missing and send update id is not provided', function () {
    $this->actingAs(TestDataSeeder::createAdminUser());

    $response = $this->getJson('/quotes/lead-history');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['quoteId', 'quoteTypeId']);
});

it('returns empty data when send update id does not exist', function () {
    $this->actingAs(TestDataSeeder::createAdminUser());

    $response = $this->getJson('/quotes/lead-history?sendUpdateId=999999');

    $response->assertSuccessful();
    expect($response->json())->toBe([]);
});
