<?php

declare(strict_types=1);

use App\Enums\LookupsEnum;
use App\Enums\QuoteTypeId;
use App\Services\CRUDService;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::ensureMinimalSchema();
});

test('getGenderOptions for health returns code to text map from gender lookups', function () {
    DB::table('lookups')->insert([
        ['key' => LookupsEnum::GENDER->value, 'code' => 'M', 'text' => 'Male', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['key' => LookupsEnum::GENDER->value, 'code' => 'F', 'text' => 'Female', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $options = app(CRUDService::class)->getGenderOptions(QuoteTypeId::Health);

    expect($options)->toBe([
        'M' => 'Male',
        'F' => 'Female',
    ]);
});

test('getGenderOptions for health reads gender lookups from cache after first load', function () {
    DB::table('lookups')->insert([
        ['key' => LookupsEnum::GENDER->value, 'code' => 'M', 'text' => 'Male', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $crudService = app(CRUDService::class);
    expect($crudService->getGenderOptions(QuoteTypeId::Health))->toBe(['M' => 'Male']);

    DB::table('lookups')->where('key', LookupsEnum::GENDER->value)->delete();

    expect($crudService->getGenderOptions(QuoteTypeId::Health))->toBe(['M' => 'Male']);
});
