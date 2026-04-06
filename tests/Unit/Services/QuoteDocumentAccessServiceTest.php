<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\CarQuote;
use App\Services\QuoteDocumentAccessService;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

test('service allows admin without matching advisor id', function () {
    $admin = TestDataSeeder::createUserWithRole(RolesEnum::Admin);
    $other = TestDataSeeder::createUser(['email' => 'other@example.com']);
    $quote = CarQuote::factory()->create(['advisor_id' => $other->id]);

    $service = new QuoteDocumentAccessService;

    expect($service->userCanAccessQuoteDocumentable($admin, $quote))->toBeTrue();
});

test('service denies car advisor when not assigned to quote', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor, ['email' => 'a@example.com']);
    $otherAdvisor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor, ['email' => 'b@example.com']);
    $quote = CarQuote::factory()->create(['advisor_id' => $advisor->id]);

    $service = new QuoteDocumentAccessService;

    expect($service->userCanAccessQuoteDocumentable($otherAdvisor, $quote))->toBeFalse();
});

test('service allows car advisor when assigned to quote', function () {
    $advisor = TestDataSeeder::createUserWithRole(RolesEnum::CarAdvisor);
    $quote = CarQuote::factory()->create(['advisor_id' => $advisor->id]);

    $service = new QuoteDocumentAccessService;

    expect($service->userCanAccessQuoteDocumentable($advisor, $quote))->toBeTrue();
});

test('service allows car manager when not assigned as advisor on quote', function () {
    $manager = TestDataSeeder::createUserWithRole(RolesEnum::CarManager);
    $otherAdvisor = TestDataSeeder::createUser(['email' => 'assigned@example.com']);
    $quote = CarQuote::factory()->create(['advisor_id' => $otherAdvisor->id]);

    $service = new QuoteDocumentAccessService;

    expect($service->userCanAccessQuoteDocumentable($manager, $quote))->toBeTrue();
});
