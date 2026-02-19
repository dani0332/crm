<?php

declare(strict_types=1);

use App\Enums\EmirateEnum;
use App\Enums\HealthPlanTypeEnum;
use App\Enums\HealthRoutingSourceEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteSegmentEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\TeamCategoryEnum;
use App\Enums\TeamNameEnum;
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
        // Ensure is_error_email_sent column exists (required for assignTeamBasedOnPrices)
        if (! $db->getSchemaBuilder()->hasColumn('health_quote_request', 'is_error_email_sent')) {
            $db->getSchemaBuilder()->table('health_quote_request', function ($table) {
                $table->boolean('is_error_email_sent')->default(0)->after('health_team_type');
            });
        }
        // Ensure plan_id column exists (required for determinePriceStartingFrom)
        if (! $db->getSchemaBuilder()->hasColumn('health_quote_request', 'plan_id')) {
            $db->getSchemaBuilder()->table('health_quote_request', function ($table) {
                $table->unsignedBigInteger('plan_id')->nullable()->after('premium');
            });
        }
    }

    // Ensure quote_tags table exists (required for isSIC() method)
    if (! $db->getSchemaBuilder()->hasTable('quote_tags')) {
        $db->getSchemaBuilder()->create('quote_tags', function ($table) {
            $table->id();
            $table->unsignedBigInteger('quote_type_id');
            $table->string('quote_uuid');
            $table->string('name');
            $table->integer('value')->nullable();
            $table->timestamps();
        });
    }

    // Ensure quote_type table exists (required for quote_type_id)
    if (! $db->getSchemaBuilder()->hasTable('quote_type')) {
        $db->getSchemaBuilder()->create('quote_type', function ($table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('text')->nullable();
            $table->boolean('is_active')->default(1);
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

    // Ensure teams table has category column (required for fetchTeamByPriceAndCategory)
    if ($db->getSchemaBuilder()->hasTable('teams')) {
        if (! $db->getSchemaBuilder()->hasColumn('teams', 'category')) {
            $db->getSchemaBuilder()->table('teams', function ($table) {
                $table->string('category')->nullable()->after('allocation_threshold_enabled');
            });
        }
    }
});

test('assigns team based on health plan type for non-AUH non-PEC lead with ENTRY_LEVEL plan', function () {
    $db = DB::connection('sqlite');

    $lead = $db->table('health_quote_request')->insertGetId([
        'uuid' => 'TEST-001',
        'code' => 'HEA-TEST-001',
        'emirate_of_your_visa_id' => EmirateEnum::DUBAI,
        'source' => LeadSourceEnum::CALL_DESK,
        'price_starting_from' => 1500.00,
        'health_plan_type_id' => HealthPlanTypeEnum::ENTRY_LEVEL->value,
        'health_team_type' => null,
        'pec_marked_at' => null,
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
    expect($healthQuote->health_team_type)->toBe(TeamNameEnum::EBP);
});

test('assigns team based on health plan type for non-AUH non-PEC lead with GOOD plan', function () {
    $db = DB::connection('sqlite');

    $lead = $db->table('health_quote_request')->insertGetId([
        'uuid' => 'TEST-002',
        'code' => 'HEA-TEST-002',
        'emirate_of_your_visa_id' => EmirateEnum::DUBAI,
        'source' => LeadSourceEnum::CALL_DESK,
        'price_starting_from' => 2000.00,
        'health_plan_type_id' => HealthPlanTypeEnum::GOOD->value,
        'health_team_type' => null,
        'pec_marked_at' => null,
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
    expect($healthQuote->health_team_type)->toBe(TeamNameEnum::RM_SPEED);
});

test('assigns team based on health plan type for non-AUH non-PEC lead with BEST plan', function () {
    $db = DB::connection('sqlite');

    $lead = $db->table('health_quote_request')->insertGetId([
        'uuid' => 'TEST-003',
        'code' => 'HEA-TEST-003',
        'emirate_of_your_visa_id' => EmirateEnum::DUBAI,
        'source' => LeadSourceEnum::CALL_DESK,
        'price_starting_from' => 3000.00,
        'health_plan_type_id' => HealthPlanTypeEnum::BEST->value,
        'health_team_type' => null,
        'pec_marked_at' => null,
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
    expect($healthQuote->health_team_type)->toBe(TeamNameEnum::RM_NB);
});

test('uses price-based assignment when shouldUseHealthPlanType returns false', function () {
    $db = DB::connection('sqlite');

    // Create team for price-based assignment (AUH category)
    $teamId = $db->table('teams')->insertGetId([
        'name' => TeamNameEnum::RM_SPEED,
        'type' => 'TEAM',
        'is_active' => 1,
        'min_price' => 1000,
        'max_price' => 2000,
        'allocation_threshold_enabled' => 1,
        'category' => TeamCategoryEnum::AUH->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create AUH SIC lead (shouldUseHealthPlanType will return false, then SIC check will use price-based)
    $lead = $db->table('health_quote_request')->insertGetId([
        'uuid' => 'TEST-004',
        'code' => 'HEA-TEST-004',
        'emirate_of_your_visa_id' => EmirateEnum::ABU_DHABI,
        'source' => LeadSourceEnum::IMCRM,
        'price_starting_from' => 1500.00,
        'health_plan_type_id' => HealthPlanTypeEnum::ENTRY_LEVEL->value,
        'health_team_type' => null,
        'pec_marked_at' => null,
        'quote_status_id' => QuoteStatusEnum::Qualified,
        'advisor_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create SIC tag for this lead
    $db->table('quote_tags')->insert([
        'quote_type_id' => QuoteTypes::HEALTH->id(),
        'quote_uuid' => 'TEST-004',
        'name' => QuoteSegmentEnum::SIC->tag(),
        'value' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $healthQuote = HealthQuote::find($lead);
    $healthQuote->setConnection('sqlite');

    // Verify it's AUH lead (shouldUseHealthPlanType will return false)
    expect($healthQuote->isAUHLead())->toBeTrue();
    // Verify it's SIC lead (will use price-based assignment)
    expect($healthQuote->isSIC(QuoteTypes::HEALTH))->toBeTrue();

    $allocationRequest = new AllocationRequest(
        quoteType: QuoteTypes::HEALTH,
        quoteUUID: $healthQuote->uuid,
        source: HealthRoutingSourceEnum::ROUTING
    );
    $allocationRequest->setLead($healthQuote);

    $pipe = new AssignTeamPipe;
    $pipe->handle($allocationRequest, fn ($request) => $request);

    $healthQuote->refresh();
    // Should use price-based assignment, not health plan type
    expect($healthQuote->health_team_type)->toBe(TeamNameEnum::RM_SPEED);
});
