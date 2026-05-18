<?php

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Models\LeadSource;
use App\Models\QuoteType;
use App\Models\Role;
use App\Models\Rule;
use App\Models\RuleLeadSource;
use App\Models\RuleType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    // Create required test schema
    TestSchemaCreator::createRulesSchema();

    // Create and authenticate a user for testing
    $this->user = User::factory()->create();
    $adminRole = Role::firstOrCreate(['name' => RolesEnum::Admin, 'guard_name' => 'web']);
    $this->user->assignRole($adminRole);

    foreach ([
        PermissionsEnum::RULE_CONFIG_LIST,
        PermissionsEnum::RULE_CONFIG_CREATE,
        PermissionsEnum::RULE_CONFIG_UPDATE,
    ] as $permissionName) {
        Permission::firstOrCreate([
            'name' => $permissionName,
            'guard_name' => 'web',
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    $this->user->givePermissionTo([
        PermissionsEnum::RULE_CONFIG_LIST,
        PermissionsEnum::RULE_CONFIG_CREATE,
        PermissionsEnum::RULE_CONFIG_UPDATE,
    ]);

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

    test('automatically sets is_applicable_for_rules to true when creating rule with non-applicable lead source', function () {
        // Create a lead source with is_applicable_for_rules set to false
        $nonApplicableLeadSource = DB::table('lead_sources')->insertGetId([
            'name' => 'Non-Applicable Lead Source',
            'code' => 'non-applicable',
            'is_active' => true,
            'is_applicable_for_rules' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ruleData = [
            'name' => 'Test Auto-Applicable Rule',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'rule_users' => [$this->testUser1->id],
            'lead_source_id' => $nonApplicableLeadSource,
            'utm_source' => 'google',
            'utm_campaign' => 'test_campaign',
            'utm_medium' => 'cpc',
        ];

        $response = $this->post(route('rule.store'), $ruleData);

        $response->assertRedirect();
        $response->assertSessionHas('message', 'Rule is created successfully.');

        // Assert lead source is now applicable for rules
        $this->assertDatabaseHas('lead_sources', [
            'id' => $nonApplicableLeadSource,
            'is_applicable_for_rules' => true,
        ]);
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

        // Update the rule with same lead source (id=1) but different UTM parameters
        // Controller logic requires lead_source_id == 1 to update rule_details
        $updateData = [
            'name' => 'Updated Lead Source Rule',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => false,
            'rule_users' => [$this->testUser2->id],
            'lead_source_id' => $this->leadSource->id, // Use existing lead source (id=1)
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
            'lead_source_id' => $this->leadSource->id,
            'utm_source' => 'instagram',
            'utm_campaign' => 'spring_sale',
            'utm_medium' => 'organic',
        ]);

        // Assert rule_lead_sources was updated with new user
        $this->assertDatabaseHas('rule_lead_sources', [
            'rule_id' => $rule->id,
            'lead_source_id' => $this->leadSource->id,
            'user_id' => $this->testUser2->id,
        ]);

        // Verify only one rule_lead_sources record exists
        $ruleLeadSourcesCount = RuleLeadSource::where('rule_id', $rule->id)->count();
        expect($ruleLeadSourcesCount)->toBe(1);
    });

    test('automatically sets is_applicable_for_rules to true when updating rule with non-applicable lead source', function () {
        // Create a lead source with is_applicable_for_rules set to false
        $nonApplicableLeadSource = DB::table('lead_sources')->insertGetId([
            'name' => 'Non-Applicable Lead Source 2',
            'code' => 'non-applicable-2',
            'is_active' => true,
            'is_applicable_for_rules' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create initial rule with different lead source
        $rule = Rule::create([
            'name' => 'Original Rule',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
        ]);

        $rule->users()->attach([$this->testUser1->id]);

        DB::table('rule_details')->insert([
            'rule_id' => $rule->id,
            'lead_source_id' => $this->leadSource->id,
            'utm_source' => 'twitter',
            'utm_campaign' => 'test',
            'utm_medium' => 'social',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update the rule to use the non-applicable lead source
        $updateData = [
            'name' => 'Updated Rule with Non-Applicable Lead Source',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'rule_users' => [$this->testUser1->id],
            'lead_source_id' => $nonApplicableLeadSource,
            'utm_source' => 'facebook',
            'utm_campaign' => 'update_test',
            'utm_medium' => 'cpc',
        ];

        $response = $this->put(route('rule.update', $rule->id), $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('message', 'Rule is updated successfully.');

        // Assert lead source is now applicable for rules
        $this->assertDatabaseHas('lead_sources', [
            'id' => $nonApplicableLeadSource,
            'is_applicable_for_rules' => true,
        ]);
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

describe('Rule Type Change - Cleanup', function () {
    test('deletes rule_detail and rule_lead_sources when changing from lead source to another type', function () {
        // Create a lead source rule with detail and lead sources
        $rule = Rule::create([
            'name' => 'Lead Source to Normal Rule',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
        ]);

        $rule->users()->attach([$this->testUser1->id, $this->testUser2->id]);

        DB::table('rule_details')->insert([
            'rule_id' => $rule->id,
            'lead_source_id' => $this->leadSource->id,
            'utm_source' => 'google',
            'utm_campaign' => 'test_campaign',
            'utm_medium' => 'cpc',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('rule_lead_sources')->insert([
            [
                'rule_id' => $rule->id,
                'lead_source_id' => $this->leadSource->id,
                'user_id' => $this->testUser1->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rule_id' => $rule->id,
                'lead_source_id' => $this->leadSource->id,
                'user_id' => $this->testUser2->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Verify data exists before update
        $this->assertDatabaseHas('rule_details', ['rule_id' => $rule->id]);
        expect(DB::table('rule_lead_sources')->where('rule_id', $rule->id)->count())->toBe(2);

        // Change rule type from lead source to car make and model
        $updateData = [
            'name' => 'Lead Source to Normal Rule',
            'rule_type' => $this->carMakeModelRuleType->id, // Change to normal type
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'rule_users' => [$this->testUser1->id],
        ];

        $response = $this->put(route('rule.update', $rule->id), $updateData);

        $response->assertRedirect();

        // Verify rule_detail lead_source_id was set to null
        $this->assertDatabaseHas('rule_details', [
            'rule_id' => $rule->id,
            'lead_source_id' => null,
        ]);

        // Verify all rule_lead_sources were deleted
        expect(DB::table('rule_lead_sources')->where('rule_id', $rule->id)->count())->toBe(0);

        // Verify rule was updated
        $this->assertDatabaseHas('rules', [
            'id' => $rule->id,
            'rule_type' => $this->carMakeModelRuleType->id,
        ]);
    });

    test('validates unique combination of lead source and utm parameters on create', function () {
        // Create first rule with specific UTM combination
        DB::table('rules')->insert([
            'name' => 'First Rule',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $firstRuleId = DB::getPdo()->lastInsertId();

        DB::table('rule_details')->insert([
            'rule_id' => $firstRuleId,
            'lead_source_id' => $this->leadSource->id,
            'utm_source' => 'google',
            'utm_campaign' => 'summer2024',
            'utm_medium' => 'cpc',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Try to create a second rule with the same combination (name is part of uniqueness in RuleRequest)
        $duplicateRuleData = [
            'name' => 'First Rule',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'rule_users' => [$this->testUser1->id],
            'lead_source_id' => $this->leadSource->id,
            'utm_source' => 'google',
            'utm_campaign' => 'summer2024',
            'utm_medium' => 'cpc',
        ];

        $response = $this->postJson(route('rule.store'), $duplicateRuleData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lead_source_id']);

        expect($response->json('errors.lead_source_id.0'))
            ->toContain('The combination of Quote Type,Lead Source, UTM Campaign,Rule Name and Rule Type already exists.');
    });

    test('allows same utm parameters with different lead source', function () {
        // Create first rule with specific UTM combination
        DB::table('rules')->insert([
            'name' => 'First Rule',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $firstRuleId = DB::getPdo()->lastInsertId();

        DB::table('rule_details')->insert([
            'rule_id' => $firstRuleId,
            'lead_source_id' => $this->leadSource->id,
            'utm_source' => 'google',
            'utm_campaign' => 'summer2024',
            'utm_medium' => 'cpc',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a different lead source
        DB::table('lead_sources')->insert([
            'name' => 'Different Lead Source',
            'code' => 'diff-source',
            'is_active' => 1,
            'is_applicable_for_rules' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $differentLeadSourceId = DB::getPdo()->lastInsertId();

        // Create rule with same UTM but different lead source - should succeed
        $ruleData = [
            'name' => 'Second Rule - Different Lead Source',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'rule_users' => [$this->testUser1->id],
            'lead_source_id' => $differentLeadSourceId,
            'utm_source' => 'google',
            'utm_campaign' => 'summer2024',
            'utm_medium' => 'cpc',
        ];

        $response = $this->post(route('rule.store'), $ruleData);

        $response->assertRedirect();

        $this->assertDatabaseHas('rule_details', [
            'lead_source_id' => $differentLeadSourceId,
            'utm_source' => 'google',
            'utm_campaign' => 'summer2024',
            'utm_medium' => 'cpc',
        ]);
    });

    test('validates unique combination on update but allows same rule to keep its values', function () {
        // Create first rule
        $ruleId = DB::table('rules')->insertGetId([
            'name' => 'First Rule',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('rule_details')->insert([
            'rule_id' => $ruleId,
            'lead_source_id' => $this->leadSource->id,
            'utm_source' => 'google',
            'utm_campaign' => 'summer2024',
            'utm_medium' => 'cpc',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('rule_users')->insert([
            'rule_id' => $ruleId,
            'user_id' => $this->testUser1->id,
        ]);

        // Update the same rule with the same values - should succeed
        $updateData = [
            'name' => 'First Rule - Updated Name',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'rule_users' => [$this->testUser1->id],
            'lead_source_id' => $this->leadSource->id,
            'utm_source' => 'google',
            'utm_campaign' => 'summer2024',
            'utm_medium' => 'cpc',
        ];

        $response = $this->put(route('rule.update', $ruleId), $updateData);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $rule = DB::table('rules')->where('id', $ruleId)->first();
        expect($rule->name)->toBe('First Rule - Updated Name');
    });

    test('validates unique combination prevents update to duplicate combination', function () {
        // Create first rule
        DB::table('rules')->insert([
            'name' => 'First Rule',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $firstRuleId = DB::getPdo()->lastInsertId();

        DB::table('rule_details')->insert([
            'rule_id' => $firstRuleId,
            'lead_source_id' => $this->leadSource->id,
            'utm_source' => 'google',
            'utm_campaign' => 'summer2024',
            'utm_medium' => 'cpc',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create second rule with different UTM
        DB::table('rules')->insert([
            'name' => 'Second Rule',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $secondRuleId = DB::getPdo()->lastInsertId();

        DB::table('rule_details')->insert([
            'rule_id' => $secondRuleId,
            'lead_source_id' => $this->leadSource->id,
            'utm_source' => 'facebook',
            'utm_campaign' => 'winter2024',
            'utm_medium' => 'social',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Try to update second rule to match first rule's combination (name is part of uniqueness in RuleRequest)
        $updateData = [
            'name' => 'First Rule',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'rule_users' => [$this->testUser1->id],
            'lead_source_id' => $this->leadSource->id,
            'utm_source' => 'google',
            'utm_campaign' => 'summer2024',
            'utm_medium' => 'cpc',
        ];

        $response = $this->putJson(route('rule.update', $secondRuleId), $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lead_source_id']);

        expect($response->json('errors.lead_source_id.0'))
            ->toContain('The combination of Quote Type,Lead Source, UTM Campaign,Rule Name and Rule Type already exists.');
    });

    test('allows different utm combinations with same lead source', function () {
        // Create first rule
        DB::table('rules')->insert([
            'name' => 'First Rule',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $firstRuleId = DB::getPdo()->lastInsertId();

        DB::table('rule_details')->insert([
            'rule_id' => $firstRuleId,
            'lead_source_id' => $this->leadSource->id,
            'utm_source' => 'google',
            'utm_campaign' => 'summer2024',
            'utm_medium' => 'cpc',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create rule with same lead source but different UTM - should succeed
        $ruleData = [
            'name' => 'Second Rule - Different UTM',
            'rule_type' => $this->leadSourceRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'rule_users' => [$this->testUser1->id],
            'lead_source_id' => $this->leadSource->id,
            'utm_source' => 'facebook',
            'utm_campaign' => 'winter2024',
            'utm_medium' => 'social',
        ];

        $response = $this->post(route('rule.store'), $ruleData);

        $response->assertRedirect();

        $this->assertDatabaseHas('rule_details', [
            'lead_source_id' => $this->leadSource->id,
            'utm_source' => 'facebook',
            'utm_campaign' => 'winter2024',
            'utm_medium' => 'social',
        ]);
    });
});

describe('Rules authorization', function () {
    test('advisor with only rule.create cannot create allocation rules', function () {
        $advisor = User::factory()->create();
        Role::firstOrCreate(['name' => RolesEnum::CarAdvisor, 'guard_name' => 'web']);
        $advisor->assignRole(RolesEnum::CarAdvisor);
        Permission::firstOrCreate(['name' => 'rule.create', 'guard_name' => 'web']);
        $advisor->givePermissionTo('rule.create');
        $this->actingAs($advisor);

        $ruleData = [
            'name' => 'Unauthorized Rule',
            'rule_type' => $this->carMakeModelRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'rule_users' => [$this->testUser1->id],
        ];

        $response = $this->post(route('rule.store'), $ruleData);

        $response->assertForbidden();
    });

    test('user with only rule-config-list cannot create allocation rules', function () {
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => RolesEnum::CarAdvisor, 'guard_name' => 'web']);
        $user->assignRole(RolesEnum::CarAdvisor);
        Permission::firstOrCreate(['name' => PermissionsEnum::RULE_CONFIG_LIST, 'guard_name' => 'web']);
        $user->givePermissionTo(PermissionsEnum::RULE_CONFIG_LIST);
        $this->actingAs($user);

        $ruleData = [
            'name' => 'List Only Rule',
            'rule_type' => $this->carMakeModelRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'rule_users' => [$this->testUser1->id],
        ];

        $response = $this->post(route('rule.store'), $ruleData);

        $response->assertForbidden();
    });

    test('user with only rule-config-list can open rules list when not a retail-only advisor', function () {
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => RolesEnum::LeadPool, 'guard_name' => 'web']);
        $user->assignRole(RolesEnum::LeadPool);
        Permission::firstOrCreate(['name' => PermissionsEnum::RULE_CONFIG_LIST, 'guard_name' => 'web']);
        $user->givePermissionTo(PermissionsEnum::RULE_CONFIG_LIST);
        $this->actingAs($user);

        $response = $this->get(route('rule.index'));

        $response->assertOk();
    });

    test('car advisor with rule-config-list can open rules list', function () {
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => RolesEnum::CarAdvisor, 'guard_name' => 'web']);
        $user->assignRole(RolesEnum::CarAdvisor);
        Permission::firstOrCreate(['name' => PermissionsEnum::RULE_CONFIG_LIST, 'guard_name' => 'web']);
        $user->givePermissionTo(PermissionsEnum::RULE_CONFIG_LIST);
        $this->actingAs($user);

        $response = $this->get(route('rule.index'));

        $response->assertOk();
    });

    test('retail advisor with rule-config-list and a manager role can open rules list', function () {
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => RolesEnum::CarAdvisor, 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => RolesEnum::CarManager, 'guard_name' => 'web']);
        $user->assignRole(RolesEnum::CarAdvisor);
        $user->assignRole(RolesEnum::CarManager);
        Permission::firstOrCreate(['name' => PermissionsEnum::RULE_CONFIG_LIST, 'guard_name' => 'web']);
        $user->givePermissionTo(PermissionsEnum::RULE_CONFIG_LIST);
        $this->actingAs($user);

        $response = $this->get(route('rule.index'));

        $response->assertOk();
    });

    test('car advisor with rule-config-list can view rule detail', function () {
        $rule = Rule::create([
            'name' => 'Rule Detail Idor Advisor',
            'rule_type' => $this->carMakeModelRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
        ]);
        $rule->users()->attach([$this->testUser1->id]);

        $user = User::factory()->create();
        Role::firstOrCreate(['name' => RolesEnum::CarAdvisor, 'guard_name' => 'web']);
        $user->assignRole(RolesEnum::CarAdvisor);
        Permission::firstOrCreate(['name' => PermissionsEnum::RULE_CONFIG_LIST, 'guard_name' => 'web']);
        $user->givePermissionTo(PermissionsEnum::RULE_CONFIG_LIST);
        $this->actingAs($user);

        $response = $this->get(route('rule.show', $rule->id));

        $response->assertOk();
    });

    test('user with only rule-config-create cannot open rules list', function () {
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => RolesEnum::CarAdvisor, 'guard_name' => 'web']);
        $user->assignRole(RolesEnum::CarAdvisor);
        Permission::firstOrCreate(['name' => PermissionsEnum::RULE_CONFIG_CREATE, 'guard_name' => 'web']);
        $user->givePermissionTo(PermissionsEnum::RULE_CONFIG_CREATE);
        $this->actingAs($user);

        $response = $this->get(route('rule.index'));

        $response->assertForbidden();
    });

    test('user with rule-config-list and rule-config-create can create allocation rules', function () {
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => RolesEnum::CarAdvisor, 'guard_name' => 'web']);
        $user->assignRole(RolesEnum::CarAdvisor);
        Permission::firstOrCreate(['name' => PermissionsEnum::RULE_CONFIG_LIST, 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => PermissionsEnum::RULE_CONFIG_CREATE, 'guard_name' => 'web']);
        $user->givePermissionTo([PermissionsEnum::RULE_CONFIG_LIST, PermissionsEnum::RULE_CONFIG_CREATE]);
        $this->actingAs($user);

        $ruleData = [
            'name' => 'Authorized Rule',
            'rule_type' => $this->carMakeModelRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
            'rule_users' => [$this->testUser1->id, $this->testUser2->id],
        ];

        $response = $this->post(route('rule.store'), $ruleData);

        $response->assertRedirect();
        $response->assertSessionHas('message', 'Rule is created successfully.');
    });

    test('advisor with only rule.edit cannot open rule edit page', function () {
        $rule = Rule::create([
            'name' => 'Rule For Edit Idor Test',
            'rule_type' => $this->carMakeModelRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
        ]);
        $rule->users()->attach([$this->testUser1->id]);

        $advisor = User::factory()->create();
        Role::firstOrCreate(['name' => RolesEnum::CarAdvisor, 'guard_name' => 'web']);
        $advisor->assignRole(RolesEnum::CarAdvisor);
        Permission::firstOrCreate(['name' => 'rule.edit', 'guard_name' => 'web']);
        $advisor->givePermissionTo('rule.edit');
        $this->actingAs($advisor);

        $response = $this->get(route('rule.edit', $rule->id));

        $response->assertForbidden();
    });

    test('advisor with only rule.edit cannot update allocation rules', function () {
        $rule = Rule::create([
            'name' => 'Rule For Update Idor Test',
            'rule_type' => $this->carMakeModelRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
        ]);
        $rule->users()->attach([$this->testUser1->id]);

        $advisor = User::factory()->create();
        Role::firstOrCreate(['name' => RolesEnum::CarAdvisor, 'guard_name' => 'web']);
        $advisor->assignRole(RolesEnum::CarAdvisor);
        Permission::firstOrCreate(['name' => 'rule.edit', 'guard_name' => 'web']);
        $advisor->givePermissionTo('rule.edit');
        $this->actingAs($advisor);

        $updateData = [
            'name' => 'Hacked Rule Name',
            'rule_type' => $this->carMakeModelRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => false,
            'rule_users' => [$this->testUser2->id],
        ];

        $response = $this->put(route('rule.update', $rule->id), $updateData);

        $response->assertForbidden();

        $this->assertDatabaseHas('rules', [
            'id' => $rule->id,
            'name' => 'Rule For Update Idor Test',
            'is_active' => true,
        ]);
    });

    test('user with only rule-config-list cannot update allocation rules', function () {
        $rule = Rule::create([
            'name' => 'Rule List Only Update Test',
            'rule_type' => $this->carMakeModelRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
        ]);
        $rule->users()->attach([$this->testUser1->id]);

        $user = User::factory()->create();
        Role::firstOrCreate(['name' => RolesEnum::CarAdvisor, 'guard_name' => 'web']);
        $user->assignRole(RolesEnum::CarAdvisor);
        Permission::firstOrCreate(['name' => PermissionsEnum::RULE_CONFIG_LIST, 'guard_name' => 'web']);
        $user->givePermissionTo(PermissionsEnum::RULE_CONFIG_LIST);
        $this->actingAs($user);

        $updateData = [
            'name' => 'Should Not Apply',
            'rule_type' => $this->carMakeModelRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => false,
            'rule_users' => [$this->testUser2->id],
        ];

        $response = $this->put(route('rule.update', $rule->id), $updateData);

        $response->assertForbidden();
    });

    test('user with rule-config-list and rule-config-update can update allocation rules', function () {
        $rule = Rule::create([
            'name' => 'Rule Update Permission Test',
            'rule_type' => $this->carMakeModelRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => true,
        ]);
        $rule->users()->attach([$this->testUser1->id]);

        $user = User::factory()->create();
        Role::firstOrCreate(['name' => RolesEnum::CarAdvisor, 'guard_name' => 'web']);
        $user->assignRole(RolesEnum::CarAdvisor);
        Permission::firstOrCreate(['name' => PermissionsEnum::RULE_CONFIG_LIST, 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => PermissionsEnum::RULE_CONFIG_UPDATE, 'guard_name' => 'web']);
        $user->givePermissionTo([PermissionsEnum::RULE_CONFIG_LIST, PermissionsEnum::RULE_CONFIG_UPDATE]);
        $this->actingAs($user);

        $updateData = [
            'name' => 'Updated By Rule Config Update',
            'rule_type' => $this->carMakeModelRuleType->id,
            'quote_type_id' => $this->quoteType->id,
            'is_active' => false,
            'rule_users' => [$this->testUser2->id],
        ];

        $response = $this->put(route('rule.update', $rule->id), $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('message', 'Rule is updated successfully.');

        $this->assertDatabaseHas('rules', [
            'id' => $rule->id,
            'name' => 'Updated By Rule Config Update',
            'is_active' => false,
        ]);
    });
});
