<?php

declare(strict_types=1);

use App\Enums\EmirateEnum;
use App\Enums\HealthPlanTypeEnum;
use App\Enums\HealthRoutingSourceEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\TeamNameEnum;
use App\Models\ApplicationStorage;
use App\Models\CanonicalNationality;
use App\Models\Emirate;
use App\Models\HealthQuote;
use App\Models\NationalityPool;
use App\Models\QuoteType;
use App\Models\Team;
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

    // Ensure notional_team column exists (may not be in CoreSchema)
    if ($db->getSchemaBuilder()->hasTable('health_quote_request')) {
        if (! $db->getSchemaBuilder()->hasColumn('health_quote_request', 'notional_team')) {
            $db->getSchemaBuilder()->table('health_quote_request', function ($table) {
                $table->unsignedBigInteger('notional_team')->nullable();
            });
        }
    }

    // Ensure LEAD_SOURCE_ECOMMERCE value exists in application_storage table (required for isEcommerce)
    ApplicationStorage::factory()->createLeadSourceEcommerceForSqlite('ecom.alfred.ae,testing.alfred.ae,staging.alfred.ae');
    ApplicationStorage::factory()->createHealthTeamRoutingEnabledForSqlite(1);

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
        QuoteType::factory()->createHealthForSqlite();
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

        CanonicalNationality::factory()->createForSqlite();
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
            $table->softDeletes();
        });

        NationalityPool::factory()->createForSqlite();
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
    Team::factory()->createForSqlite();
});

test('assigns PEC team based on health plan type and pec check', function () {
    $db = DB::connection('sqlite');

    $lead = $db->table('health_quote_request')->insertGetId([
        'uuid' => 'TEST-002',
        'code' => 'HEA-TEST-002',
        'emirate_of_your_visa_id' => EmirateEnum::DUBAI,
        'source' => LeadSourceEnum::ECOM_SOURCE,
        'price_starting_from' => 20.00,
        'health_plan_type_id' => HealthPlanTypeEnum::GOOD->value,
        'health_team_type' => null,
        'pec_marked_at' => now(),
        'quote_status_id' => QuoteStatusEnum::Qualified,
        'nationality_id' => 1,
        'advisor_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create Dubai emirate for relationship
    Emirate::factory()->create([
        'id' => EmirateEnum::DUBAI,
        'text' => 'Dubai',
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
    expect($healthQuote->health_team_type)->toBe(TeamNameEnum::PEC);
});
