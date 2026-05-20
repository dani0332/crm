<?php

declare(strict_types=1);

use App\Enums\GenericRequestEnum;
use App\Enums\LookupsEnum;
use Database\Seeders\LookupSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::ensureMinimalSchema();
});

test('health revamp gender seeding does not overwrite an existing row with the same key and code', function () {
    DB::table('lookups')->insert([
        'key' => LookupsEnum::GENDER->value,
        'code' => GenericRequestEnum::MALE_SINGLE_VALUE,
        'text' => 'Custom existing label',
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $seeder = new LookupSeeder;
    $method = new ReflectionMethod(LookupSeeder::class, 'gender');
    $method->setAccessible(true);
    $method->invoke($seeder);

    $row = DB::table('lookups')
        ->where('key', LookupsEnum::GENDER->value)
        ->where('code', GenericRequestEnum::MALE_SINGLE_VALUE)
        ->first();

    expect($row->text)->toBe('Custom existing label')
        ->and(DB::table('lookups')->where('key', LookupsEnum::GENDER->value)->count())->toBe(2);
});
