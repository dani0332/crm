<?php

declare(strict_types=1);

use App\Enums\ApplicationStorageEnums;
use App\Enums\EmirateEnum;
use App\Enums\HealthPlanTypeEnum;
use App\Enums\HealthRoutingSourceEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\TeamCategoryEnum;
use App\Enums\TeamNameEnum;
use App\Enums\TeamTypeEnum;
use App\Models\HealthQuote;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Pipes\Allocation\Health\AssignTeamPipe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    Mail::fake();

    $db = DB::connection('sqlite');

    // Ensure health_plan_type_id column exists (may not be in CoreSchema)
    if ($db->getSchemaBuilder()->hasTable('health_quote_request')) {
        if (! $db->getSchemaBuilder()->hasColumn('health_quote_request', 'health_plan_type_id')) {
            $db->getSchemaBuilder()->table('health_quote_request', function ($table) {
                $table->unsignedBigInteger('health_plan_type_id')->nullable()->after('price_starting_from');
            });
        }
    }

    // Ensure LEAD_SOURCE_ECOMMERCE value exists in application_storage table (required for isEcommerce)
    $db->table('application_storage')->insert([
        [
            'key_name' => ApplicationStorageEnums::LEAD_SOURCE_ECOMMERCE,
            'value' => 'ecom.alfred.ae,testing.alfred.ae,staging.alfred.ae',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'key_name' => ApplicationStorageEnums::HEALTH_TEAM_ROUTING_ENABLED,
            'value' => 1,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    // Ensure sic config table exists
    if (! $db->getSchemaBuilder()->hasTable('sic_configs')) {
        $db->getSchemaBuilder()->create('sic_configs', function ($table) {
            $table->id();
            $table->integer('quote_type_id');
            $table->integer('min_age');
            $table->integer('max_age');
            $table->decimal('price_starting_from', 10, 2);
            $table->timestamps();
        });
    }

    // Ensure Health quote type exists
    $healthQuoteTypeId = $db->table('quote_type')->where('code', QuoteTypes::HEALTH->value)->value('id');
    if (! $healthQuoteTypeId) {
        $db->table('quote_type')->insert([
            'id' => QuoteTypes::HEALTH->id(),
            'code' => QuoteTypes::HEALTH->value,
            'text' => QuoteTypes::HEALTH->value,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Ensure canonical_nationalities
    if (! $db->getSchemaBuilder()->hasTable('canonical_nationalities')) {
        $db->getSchemaBuilder()->create('canonical_nationalities', function ($table) {
            $table->id();
            $table->unsignedBigInteger('nationality_id');
            $table->string('canonical_nationality_code');
            $table->string('canonical_nationality_name');
            $table->integer('nationality_synonym');
            $table->timestamps();
        });

        $db->table('canonical_nationalities')->insert([
            'nationality_id' => 1,
            'canonical_nationality_code' => 'CN0001',
            'canonical_nationality_name' => 'Afghan',
            'nationality_synonym' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Ensure nationlity_pool table exists
    if (! $db->getSchemaBuilder()->hasTable('nationality_pool')) {
        $db->getSchemaBuilder()->create('nationality_pool', function ($table) {
            $table->id();
            $table->string('health_nationality_group_ids');
            $table->string('canonical_nationality_codes');
            $table->date('effective_from');
            $table->date('effective_to');
            $table->boolean('is_active');
            $table->timestamps();
        });

        $db->table('nationality_pool')->insert([
            'health_nationality_group_ids' => '1,2,3',
            'canonical_nationality_codes' => 'CN0001,CN0002',
            'effective_from' => now(),
            'effective_to' => '2099-12-31',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Ensure teams table has category column (required for fetchTeamByPriceAndCategory)
    if ($db->getSchemaBuilder()->hasTable('teams')) {
        if (! $db->getSchemaBuilder()->hasColumn('teams', 'category')) {
            $db->getSchemaBuilder()->table('teams', function ($table) {
                $table->string('category')->nullable()->after('allocation_threshold_enabled');
            });
        }
    }

    // Min GBP price
    $db->table('teams')->insert([
        'name' => TeamNameEnum::GBP,
        'code' => TeamNameEnum::GBP,
        'type' => TeamTypeEnum::TEAM,
        'is_active' => 1,
        'parent_team_id' => null,
        'category' => TeamCategoryEnum::NON_AUH->value,
        'allocation_threshold_enabled' => true,
        'min_price' => 1000,
        'max_price' => 1000000,
    ]);
});

test('assigns GBP team for AUH lead', function () {
    $db = DB::connection('sqlite');
    $lead = $db->table('health_quote_request')->insertGetId([
        'uuid' => 'TEST-001',
        'code' => 'HEA-TEST-001',
        'emirate_of_your_visa_id' => EmirateEnum::ABU_DHABI,
        'source' => LeadSourceEnum::ECOM_SOURCE,
        'price_starting_from' => 1500.00,
        'health_plan_type_id' => HealthPlanTypeEnum::ENTRY_LEVEL->value,
        'health_team_type' => null,
        'pec_marked_at' => null,
        'quote_status_id' => QuoteStatusEnum::Qualified,
        'nationality_id' => 1,
        'advisor_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $healthQuote = HealthQuote::find($lead);
    $healthQuote->setConnection('sqlite');

    $allocationRequest = new AllocationRequest(
        quoteType: QuoteTypes::HEALTH,
        quoteUUID: $healthQuote->uuid,
        source: HealthRoutingSourceEnum::ROUTING
    );
    $allocationRequest->setLead($healthQuote);

    $pipe = new AssignTeamPipe;
    $pipe->handle($allocationRequest, fn ($request) => $request);

    $healthQuote->refresh();
    expect($healthQuote->health_team_type)->toBe(TeamNameEnum::GBP);
});

test('assigns GBP for non-AUH', function () {
    $db = DB::connection('sqlite');

    $lead = $db->table('health_quote_request')->insertGetId([
        'uuid' => 'TEST-001',
        'code' => 'HEA-TEST-001',
        'emirate_of_your_visa_id' => EmirateEnum::DUBAI,
        'source' => LeadSourceEnum::ECOM_SOURCE,
        'price_starting_from' => 1500.00,
        'health_plan_type_id' => HealthPlanTypeEnum::ENTRY_LEVEL->value,
        'health_team_type' => null,
        'pec_marked_at' => null,
        'nationality_id' => 1,
        'quote_status_id' => QuoteStatusEnum::Qualified,
        'advisor_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $healthQuote = HealthQuote::find($lead);
    $healthQuote->setConnection('sqlite');

    $allocationRequest = new AllocationRequest(
        quoteType: QuoteTypes::HEALTH,
        quoteUUID: $healthQuote->uuid,
        source: HealthRoutingSourceEnum::ROUTING
    );
    $allocationRequest->setLead($healthQuote);

    $pipe = new AssignTeamPipe;
    $pipe->handle($allocationRequest, fn ($request) => $request);

    $healthQuote->refresh();
    expect($healthQuote->health_team_type)->toBe(TeamNameEnum::GBP);
});

test('assigns team based on health plan type for non-AUH lead with GOOD plan', function () {
    $db = DB::connection('sqlite');

    $lead = $db->table('health_quote_request')->insertGetId([
        'uuid' => 'TEST-002',
        'code' => 'HEA-TEST-002',
        'emirate_of_your_visa_id' => EmirateEnum::DUBAI,
        'source' => LeadSourceEnum::ECOM_SOURCE,
        'price_starting_from' => 20.00,
        'health_plan_type_id' => HealthPlanTypeEnum::GOOD->value,
        'health_team_type' => null,
        'pec_marked_at' => null,
        'quote_status_id' => QuoteStatusEnum::Qualified,
        'nationality_id' => 1,
        'advisor_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $healthQuote = HealthQuote::find($lead);
    $healthQuote->setConnection('sqlite');

    $allocationRequest = new AllocationRequest(
        quoteType: QuoteTypes::HEALTH,
        quoteUUID: $healthQuote->uuid,
        source: HealthRoutingSourceEnum::ROUTING
    );
    $allocationRequest->setLead($healthQuote);

    $pipe = new AssignTeamPipe;
    $pipe->handle($allocationRequest, fn ($request) => $request);

    $healthQuote->refresh();
    expect($healthQuote->health_team_type)->toBe(TeamNameEnum::RM_SPEED);
});
