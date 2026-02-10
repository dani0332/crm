<?php

use App\Models\LeadSource;
use App\Models\QuoteType;
use App\Models\Rule;
use App\Models\RuleLeadSource;
use App\Models\RuleType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    // Create required test schema
    TestSchemaCreator::createRulesSchema();

    // Create and authenticate a user for testing
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Create required test data using DB inserts
    $this->quoteTypeId = DB::table('quote_type')->insertGetId([
        'code' => 'CAR',
        'text' => 'Car Insurance',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $this->quoteType = QuoteType::find($this->quoteTypeId);

    // Create rule types
    DB::table('rule_types')->insert([
        'id' => 1,
        'name' => 'lead source',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $this->leadSourceRuleType = RuleType::find(1);

    DB::table('rule_types')->insert([
        'id' => 2,
        'name' => 'car make and model',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $this->carMakeModelRuleType = RuleType::find(2);

    // Create test users
    $this->testUser1 = User::factory()->create(['name' => 'Test User 1']);
    $this->testUser2 = User::factory()->create(['name' => 'Test User 2']);

    // Create lead source for lead source rule type tests
    DB::table('lead_sources')->insert([
        'name' => 'Test Lead Source',
        'code' => 'TEST_LEAD',
        'is_active' => true,
        'is_applicable_for_rules' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $this->leadSource = LeadSource::where('name', 'Test Lead Source')->first();
});

describe('Rule Creation - Normal Rule Type', function () {
    test('can create a rule with normal rule type (car make and model)', function () {
        $ruleData = [
            'name' => 'Test Car Make Rule',
            'rule_type' => $this->carMakeModelRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'rule_users' => [$this->testUser1->id, $this->testUser2->id],
        ];

        $response = $this->post(route('rule.store'), $ruleData);

        $response->assertRedirect();
        $response->assertSessionHas('message', 'Rule is created successfully.');

        // Assert rule was created in rules table
        $this->assertDatabaseHas('rules', [
            'name' => 'Test Car Make Rule',
            'rule_type' => $this->carMakeModelRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
        ]);

        $rule = Rule::where('name', 'Test Car Make Rule')->first();

        // Assert users were attached in rule_users table
        $this->assertDatabaseHas('rule_users', [
            'rule_id' => $rule->id,
            'user_id' => $this->testUser1->id,
        ]);

        $this->assertDatabaseHas('rule_users', [
            'rule_id' => $rule->id,
            'user_id' => $this->testUser2->id,
        ]);

        // Assert NO rule_details record was created (normal rule type)
        $this->assertDatabaseMissing('rule_details', [
            'rule_id' => $rule->id,
        ]);

        // Assert NO rule_lead_sources records were created (normal rule type)
        $this->assertDatabaseMissing('rule_lead_sources', [
            'rule_id' => $rule->id,
        ]);
    });

    test('validates required fields for normal rule type', function () {
        $ruleData = [
            'name' => '', // Empty name
            'rule_type' => $this->carMakeModelRuleType->id,
            'is_active' => true,
        ];

        $response = $this->post(route('rule.store'), $ruleData);

        $response->assertSessionHasErrors(['name', 'rule_users', 'quote_type_id']);
    });
});

describe('Rule Creation - Lead Source Rule Type', function () {
    test('can create a rule with lead source rule type', function () {
        $ruleData = [
            'name' => 'Test Lead Source Rule',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'rule_users' => [$this->testUser1->id, $this->testUser2->id],
            'lead_source_id' => $this->leadSource->id,
            'utm_source' => 'google',
            'utm_campaign' => 'summer_sale',
            'utm_medium' => 'cpc',
        ];

        $response = $this->post(route('rule.store'), $ruleData);

        $response->assertRedirect();
        $response->assertSessionHas('message', 'Rule is created successfully.');

        // Assert rule was created in rules table
        $this->assertDatabaseHas('rules', [
            'name' => 'Test Lead Source Rule',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
        ]);

        $rule = Rule::where('name', 'Test Lead Source Rule')->first();

        // Assert users were attached in rule_users table
        $this->assertDatabaseHas('rule_users', [
            'rule_id' => $rule->id,
            'user_id' => $this->testUser1->id,
        ]);

        $this->assertDatabaseHas('rule_users', [
            'rule_id' => $rule->id,
            'user_id' => $this->testUser2->id,
        ]);

        // Assert rule_details record was created with UTM parameters
        $this->assertDatabaseHas('rule_details', [
            'rule_id' => $rule->id,
            'lead_source_id' => $this->leadSource->id,
            'utm_source' => 'google',
            'utm_campaign' => 'summer_sale',
            'utm_medium' => 'cpc',
        ]);

        // Assert rule_lead_sources records were created for each user
        $this->assertDatabaseHas('rule_lead_sources', [
            'rule_id' => $rule->id,
            'lead_source_id' => $this->leadSource->id,
            'user_id' => $this->testUser1->id,
        ]);

        $this->assertDatabaseHas('rule_lead_sources', [
            'rule_id' => $rule->id,
            'lead_source_id' => $this->leadSource->id,
            'user_id' => $this->testUser2->id,
        ]);

        // Verify count of rule_lead_sources records
        $ruleLeadSourcesCount = RuleLeadSource::where('rule_id', $rule->id)->count();
        expect($ruleLeadSourcesCount)->toBe(2);
    });

    test('requires lead_source_id when rule type is lead source', function () {
        $ruleData = [
            'name' => 'Test Lead Source Rule Without Lead Source',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'rule_users' => [$this->testUser1->id],
            // Missing lead_source_id
        ];

        $response = $this->post(route('rule.store'), $ruleData);

        $response->assertSessionHasErrors(['lead_source_id']);
    });

    test('can create lead source rule with optional UTM parameters as null', function () {
        $ruleData = [
            'name' => 'Test Lead Source Rule Without UTM',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'rule_users' => [$this->testUser1->id],
            'lead_source_id' => $this->leadSource->id,
            // No UTM parameters
        ];

        $response = $this->post(route('rule.store'), $ruleData);

        $response->assertRedirect();
        $response->assertSessionHas('message', 'Rule is created successfully.');

        $rule = Rule::where('name', 'Test Lead Source Rule Without UTM')->first();

        // Assert rule_details was created with null UTM values
        $this->assertDatabaseHas('rule_details', [
            'rule_id' => $rule->id,
            'lead_source_id' => $this->leadSource->id,
            'utm_source' => null,
            'utm_campaign' => null,
            'utm_medium' => null,
        ]);
    });
});

describe('Rule Update - Normal Rule Type', function () {
    test('can update a rule with normal rule type', function () {
        // Create initial rule
        $rule = Rule::create([
            'name' => 'Original Rule Name',
            'rule_type' => $this->carMakeModelRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
        ]);

        $rule->users()->attach([$this->testUser1->id]);

        // Update the rule
        $updateData = [
            'name' => 'Updated Rule Name',
            'rule_type' => $this->carMakeModelRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => false,
            'rule_users' => [$this->testUser2->id], // Change user
        ];

        $response = $this->put(route('rule.update', $rule->id), $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('message', 'Rule is updated successfully.');

        // Assert rule was updated
        $this->assertDatabaseHas('rules', [
            'id' => $rule->id,
            'name' => 'Updated Rule Name',
            'is_active' => false,
        ]);

        // Assert old user was removed and new user was added
        $this->assertDatabaseMissing('rule_users', [
            'rule_id' => $rule->id,
            'user_id' => $this->testUser1->id,
        ]);

        $this->assertDatabaseHas('rule_users', [
            'rule_id' => $rule->id,
            'user_id' => $this->testUser2->id,
        ]);
    });

    test('can update rule users by syncing', function () {
        // Create initial rule with one user
        $rule = Rule::create([
            'name' => 'User Sync Test Rule',
            'rule_type' => $this->carMakeModelRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
        ]);

        $rule->users()->attach([$this->testUser1->id]);

        // Update with multiple users
        $updateData = [
            'name' => 'User Sync Test Rule',
            'rule_type' => $this->carMakeModelRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'rule_users' => [$this->testUser1->id, $this->testUser2->id],
        ];

        $response = $this->put(route('rule.update', $rule->id), $updateData);

        $response->assertRedirect();

        // Verify both users are now attached
        expect($rule->fresh()->users()->count())->toBe(2);
    });
});

describe('Rule Update - Lead Source Rule Type', function () {
    test('can update a lead source rule', function () {
        // Create initial rule with lead source
        $rule = Rule::create([
            'name' => 'Original Lead Source Rule',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
        ]);

        $rule->users()->attach([$this->testUser1->id]);

        DB::table('rule_details')->insert([
            'rule_id' => $rule->id,
            'lead_source_id' => $this->leadSource->id,
            'utm_source' => 'facebook',
            'utm_campaign' => 'winter_sale',
            'utm_medium' => 'social',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('rule_lead_sources')->insert([
            'rule_id' => $rule->id,
            'lead_source_id' => $this->leadSource->id,
            'user_id' => $this->testUser1->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a new lead source for update
        DB::table('lead_sources')->insert([
            'name' => 'New Lead Source',
            'code' => 'NEW_LEAD',
            'is_active' => true,
            'is_applicable_for_rules' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $newLeadSource = LeadSource::where('name', 'New Lead Source')->first();

        // Update the rule
        $updateData = [
            'name' => 'Updated Lead Source Rule',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => false,
            'rule_users' => [$this->testUser2->id],
            'lead_source_id' => $newLeadSource->id,
            'utm_source' => 'instagram',
            'utm_campaign' => 'spring_sale',
            'utm_medium' => 'organic',
        ];

        $response = $this->put(route('rule.update', $rule->id), $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('message', 'Rule is updated successfully.');

        // Assert rule was updated
        $this->assertDatabaseHas('rules', [
            'id' => $rule->id,
            'name' => 'Updated Lead Source Rule',
            'is_active' => false,
        ]);

        // Assert rule_details was updated
        $this->assertDatabaseHas('rule_details', [
            'rule_id' => $rule->id,
            'lead_source_id' => $newLeadSource->id,
            'utm_source' => 'instagram',
            'utm_campaign' => 'spring_sale',
            'utm_medium' => 'organic',
        ]);

        // Assert old rule_lead_sources was removed
        $this->assertDatabaseMissing('rule_lead_sources', [
            'rule_id' => $rule->id,
            'lead_source_id' => $this->leadSource->id,
        ]);

        // Assert new rule_lead_sources was created
        $this->assertDatabaseHas('rule_lead_sources', [
            'rule_id' => $rule->id,
            'lead_source_id' => $newLeadSource->id,
            'user_id' => $this->testUser2->id,
        ]);

        // Verify only one rule_lead_sources record exists
        $ruleLeadSourcesCount = RuleLeadSource::where('rule_id', $rule->id)->count();
        expect($ruleLeadSourcesCount)->toBe(1);
    });

    test('updates rule_lead_sources when users are changed', function () {
        // Create initial rule
        $rule = Rule::create([
            'name' => 'Users Update Test',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
        ]);

        $rule->users()->attach([$this->testUser1->id]);

        DB::table('rule_details')->insert([
            'rule_id' => $rule->id,
            'lead_source_id' => $this->leadSource->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('rule_lead_sources')->insert([
            'rule_id' => $rule->id,
            'lead_source_id' => $this->leadSource->id,
            'user_id' => $this->testUser1->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create additional test user
        $testUser3 = User::factory()->create(['name' => 'Test User 3']);

        // Update with different users
        $updateData = [
            'name' => 'Users Update Test',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'rule_users' => [$this->testUser2->id, $testUser3->id],
            'lead_source_id' => $this->leadSource->id,
        ];

        $response = $this->put(route('rule.update', $rule->id), $updateData);

        $response->assertRedirect();

        // Assert old rule_lead_sources was removed
        $this->assertDatabaseMissing('rule_lead_sources', [
            'rule_id' => $rule->id,
            'user_id' => $this->testUser1->id,
        ]);

        // Assert new rule_lead_sources were created
        $this->assertDatabaseHas('rule_lead_sources', [
            'rule_id' => $rule->id,
            'lead_source_id' => $this->leadSource->id,
            'user_id' => $this->testUser2->id,
        ]);

        $this->assertDatabaseHas('rule_lead_sources', [
            'rule_id' => $rule->id,
            'lead_source_id' => $this->leadSource->id,
            'user_id' => $testUser3->id,
        ]);

        // Verify correct count
        $ruleLeadSourcesCount = RuleLeadSource::where('rule_id', $rule->id)->count();
        expect($ruleLeadSourcesCount)->toBe(2);
    });

    test('removes rule_lead_sources when lead_source_id is removed', function () {
        // Create initial rule with lead source
        $rule = Rule::create([
            'name' => 'Remove Lead Source Test',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
        ]);

        $rule->users()->attach([$this->testUser1->id]);

        DB::table('rule_details')->insert([
            'rule_id' => $rule->id,
            'lead_source_id' => $this->leadSource->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('rule_lead_sources')->insert([
            'rule_id' => $rule->id,
            'lead_source_id' => $this->leadSource->id,
            'user_id' => $this->testUser1->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Change rule type to normal (car make and model)
        $updateData = [
            'name' => 'Remove Lead Source Test',
            'rule_type' => $this->carMakeModelRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'rule_users' => [$this->testUser1->id],
            // No lead_source_id provided
        ];

        $response = $this->put(route('rule.update', $rule->id), $updateData);

        $response->assertRedirect();

        // Assert all rule_lead_sources were removed
        $ruleLeadSourcesCount = RuleLeadSource::where('rule_id', $rule->id)->count();
        expect($ruleLeadSourcesCount)->toBe(0);
    });
});

describe('Rule Relationships', function () {
    test('rule loads relationships correctly', function () {
        $rule = Rule::create([
            'name' => 'Relationships Test Rule',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
        ]);

        $rule->users()->attach([$this->testUser1->id, $this->testUser2->id]);

        DB::table('rule_details')->insert([
            'rule_id' => $rule->id,
            'lead_source_id' => $this->leadSource->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Load relationships
        $rule->load(['ruleUsers', 'ruleType', 'ruleDetail', 'leadSource', 'quoteType']);

        expect($rule->ruleUsers)->toHaveCount(2);
        expect($rule->ruleType->name)->toBe('lead source');
        expect($rule->ruleDetail)->not->toBeNull();
        expect($rule->leadSource->name)->toBe('Test Lead Source');
        expect($rule->quoteType->name)->toBe('CAR'); // 'code as name' in relationship
    });
});
