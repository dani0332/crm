<?php

declare(strict_types=1);

use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function (): void {
    TestSchemaCreator::createMinimalSchema();
});

it('returns validation error when quote fields are missing', function (): void {
    $this->actingAs(TestDataSeeder::createAdminUser());

    $response = $this->getJson('/quotes/status-logs');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['quoteId', 'quoteTypeId']);
});
