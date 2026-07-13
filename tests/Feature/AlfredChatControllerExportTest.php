<?php

declare(strict_types=1);

use App\Enums\WorkflowTypeEnum;
use App\Http\Middleware\PreventRequestForgery;
use App\Services\EmailServices\WebEngageService;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    $this->user = TestDataSeeder::createUser(['email' => fake()->unique()->safeEmail()]);

    $this->actingAs($this->user);
    $this->withoutMiddleware(PreventRequestForgery::class);
});

test('exportChatViaBird triggers a WebEngage event instead of Bird', function () {
    $webEngageServiceMock = Mockery::mock(WebEngageService::class);
    $webEngageServiceMock->shouldReceive('sendEvent')
        ->once()
        ->withArgs(function (string $eventName, array $eventData) {
            return $eventName === WorkflowTypeEnum::INSTANT_CHAT_EXPORT
                && $eventData['workflowType'] === WorkflowTypeEnum::INSTANT_CHAT_EXPORT
                && $eventData['customerId'] === 'recipient@example.com'
                && $eventData['customerEmail'] === 'recipient@example.com'
                && $eventData['report'] === 'Detailed';
        })
        ->andReturn((object) ['status_code' => 200, 'body' => [], 'headers' => []]);

    $this->app->instance(WebEngageService::class, $webEngageServiceMock);

    $response = $this->postJson('/instant-alfred/export-bird', [
        'report' => 'Detailed',
        'recipientEmail' => 'recipient@example.com',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'report_type' => 'Detailed',
        ]);
});

test('exportChatViaBird returns an error when the WebEngage event fails', function () {
    $webEngageServiceMock = Mockery::mock(WebEngageService::class);
    $webEngageServiceMock->shouldReceive('sendEvent')
        ->once()
        ->andReturn((object) ['status_code' => 500, 'body' => [], 'headers' => []]);

    $this->app->instance(WebEngageService::class, $webEngageServiceMock);

    $response = $this->postJson('/instant-alfred/export-bird', [
        'report' => 'Detailed',
        'recipientEmail' => 'recipient@example.com',
    ]);

    $response->assertStatus(500)
        ->assertJson(['success' => false]);
});
