<?php

use App\Models\LeadSource;
use App\Models\User;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createRulesSchema();
    // Create and authenticate a user for testing
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('can create a new lead source with valid data', function () {
    $leadSourceData = [
        'name' => 'Test Lead Source',
        'code' => 'TEST_CODE',
        'is_active' => true,
    ];

    $response = $this->postJson(route('lead-source.store'), $leadSourceData);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Lead source created successfully.',
        ])
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'code',
                'is_active',
                'is_applicable_for_rules',
            ],
        ]);

    // Assert the lead source was created in the database (use 1 for booleans in database queries)
    $this->assertDatabaseHas('lead_sources', [
        'name' => 'Test Lead Source',
        'code' => 'TEST_CODE',
        'is_active' => 1,
        'is_applicable_for_rules' => 1,
    ]);
});

test('sets is_applicable_for_rules to true automatically', function () {
    $leadSourceData = [
        'name' => 'Auto Rules Lead Source',
        'code' => 'AUTO_RULES',
        'is_active' => true,
    ];

    $response = $this->postJson(route('lead-source.store'), $leadSourceData);

    $response->assertStatus(201);

    $leadSource = LeadSource::where('name', 'Auto Rules Lead Source')->first();

    expect($leadSource->is_applicable_for_rules)->toBeTrue();
});

test('fails to create lead source with duplicate name', function () {
    // Create an existing lead source
    LeadSource::factory()->create([
        'name' => 'Duplicate Name',
        'code' => 'DUP1',
    ]);

    // Try to create another with the same name
    $leadSourceData = [
        'name' => 'Duplicate Name',
        'code' => 'DUP2',
        'is_active' => true,
    ];

    $response = $this->postJson(route('lead-source.store'), $leadSourceData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('requires name field', function () {
    $leadSourceData = [
        'code' => 'TEST_CODE',
        'is_active' => true,
    ];

    $response = $this->postJson(route('lead-source.store'), $leadSourceData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('can create lead source without code', function () {
    $leadSourceData = [
        'name' => 'Lead Source Without Code',
        'is_active' => true,
    ];

    $response = $this->postJson(route('lead-source.store'), $leadSourceData);

    $response->assertStatus(201);

    $this->assertDatabaseHas('lead_sources', [
        'name' => 'Lead Source Without Code',
        'code' => null,
        'is_active' => 1,
        'is_applicable_for_rules' => 1,
    ]);
});

test('defaults is_active to true when not provided', function () {
    $leadSourceData = [
        'name' => 'Default Active Lead Source',
        'code' => 'DEFAULT',
    ];

    $response = $this->postJson(route('lead-source.store'), $leadSourceData);

    $response->assertStatus(201);

    $leadSource = LeadSource::where('name', 'Default Active Lead Source')->first();

    expect($leadSource->is_active)->toBeTrue();
});

test('validates name does not exceed 255 characters', function () {
    $leadSourceData = [
        'name' => str_repeat('a', 256),
        'code' => 'TEST',
        'is_active' => true,
    ];

    $response = $this->postJson(route('lead-source.store'), $leadSourceData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('validates code does not exceed 255 characters when provided', function () {
    $leadSourceData = [
        'name' => 'Test Lead Source',
        'code' => str_repeat('a', 256),
        'is_active' => true,
    ];

    $response = $this->postJson(route('lead-source.store'), $leadSourceData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

test('can create lead source with only name and all defaults are applied', function () {
    // Send minimal payload with only name
    $leadSourceData = [
        'name' => 'Minimal Lead Source',
    ];

    $response = $this->postJson(route('lead-source.store'), $leadSourceData);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Lead source created successfully.',
        ])
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'code',
                'is_active',
                'is_applicable_for_rules',
            ],
        ])
        ->assertJsonFragment([
            'name' => 'Minimal Lead Source',
            'code' => null,
            'is_active' => true,
            'is_applicable_for_rules' => true,
        ]);

    // Verify in database (use 1 for booleans in database queries)
    $this->assertDatabaseHas('lead_sources', [
        'name' => 'Minimal Lead Source',
        'code' => null,
        'is_active' => 1,
        'is_applicable_for_rules' => 1,
    ]);

    // Verify using model
    $leadSource = LeadSource::where('name', 'Minimal Lead Source')->first();

    expect($leadSource)->not->toBeNull();
    expect($leadSource->name)->toBe('Minimal Lead Source');
    expect($leadSource->code)->toBeNull();
    expect($leadSource->is_active)->toBeTrue();
    expect($leadSource->is_applicable_for_rules)->toBeTrue();
});
