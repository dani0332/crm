<?php

declare(strict_types=1);

use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Middleware\VerifyCsrfToken;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    $this->withoutMiddleware(VerifyCsrfToken::class);
});

it('returns advisors as a serialized collection and count matches data length', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor, [
        'email' => fake()->unique()->safeEmail(),
        'name' => 'Alpha Advisor',
    ]);

    $this->actingAs($advisor);

    $response = $this->postJson(route('advisors.by-quote-type'), [
        'quote_type' => QuoteTypes::CAR->value,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $data = $response->json('data');
    expect($data)->toBeArray();
    expect($response->json('count'))->toBe(count($data));
    expect(collect($data)->pluck('id')->all())->toContain($advisor->id);
});
