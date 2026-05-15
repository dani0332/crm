<?php

declare(strict_types=1);

use App\Enums\QuoteTypeId;
use App\Models\BusinessQuote;
use App\Models\Entity;
use App\Services\AMLService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    if (! Schema::hasTable('entities')) {
        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->string('trade_license_no')->nullable();
            $table->string('company_name')->nullable();
            $table->text('company_address')->nullable();
            $table->string('industry_type_code')->nullable();
            $table->unsignedBigInteger('emirate_of_registration_id')->nullable();
            $table->string('code')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('quote_request_entity_mapping')) {
        Schema::create('quote_request_entity_mapping', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quote_type_id');
            $table->unsignedBigInteger('quote_request_id');
            $table->unsignedBigInteger('entity_id');
            $table->string('entity_type_code')->nullable();
            $table->timestamps();
        });
    }
});

it('manages handleEntityData transactions: no nesting when an outer transaction is open, and a single level when not', function () {
    $capturedLevels = [];
    Entity::saving(function () use (&$capturedLevels) {
        $capturedLevels[] = DB::transactionLevel();

        return true;
    });

    $method = new ReflectionMethod(AMLService::class, 'handleEntityData');
    $method->setAccessible(true);
    $service = new AMLService;

    $baseRequest = fn (string $tl) => (object) [
        'screening_id_number' => $tl,
        'company_name' => 'Unit Test Co',
        'company_address' => 'Dubai',
        'industry_type_code' => 'IT',
        'emirate_of_registration_id' => 1,
        'entity_type_code' => 'LLC',
    ];

    $insertBusinessQuote = function (string $code) {
        $id = DB::table('business_quote_request')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            // Not Group Medical (5): avoids $quote->save() in handleEntityData; full test DB lacks
            // business_quote_request_detail and related observer side effects. Transaction behaviour
            // under test is the same (entity + mapping still run inside the closure).
            'business_type_of_insurance_id' => 3,
            'quote_status_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return BusinessQuote::query()->findOrFail($id);
    };

    $quoteA = $insertBusinessQuote('BQR-UT-A');

    $capturedLevels = [];
    try {
        DB::beginTransaction();
        $method->invoke($service, $baseRequest('TL-OUTER-'.Str::random(6)), QuoteTypeId::Business, $quoteA);
        // Nested DB::transaction() would yield 2 here; we join the outer transaction.
        expect($capturedLevels[0] ?? null)->toBe(1);
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
    }

    $capturedLevels = [];
    $quoteB = $insertBusinessQuote('BQR-UT-B');

    expect(DB::transactionLevel())->toBe(0);
    $entityId = $method->invoke($service, $baseRequest('TL-INNER-'.Str::random(6)), QuoteTypeId::Business, $quoteB);
    expect($entityId)->toBeInt()->toBeGreaterThan(0);
    expect(DB::transactionLevel())->toBe(0);
    expect($capturedLevels[0] ?? null)->toBe(1);
});
