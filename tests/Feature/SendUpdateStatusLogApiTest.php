<?php

declare(strict_types=1);

use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function (): void {
    TestSchemaCreator::createMinimalSchema();
});

it('validates send update log id for send update status logs endpoint', function (): void {
    $this->actingAs(TestDataSeeder::createAdminUser());

    $response = $this->getJson('/quotes/send-update-status-logs');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['sendUpdateLogId']);
});

it('returns empty data when no status logs exist for the send update log id', function (): void {
    $this->actingAs(TestDataSeeder::createAdminUser());

    $response = $this->getJson('/quotes/send-update-status-logs?sendUpdateLogId=999999999');

    $response->assertSuccessful();
    expect($response->json())->toBe([]);
});
