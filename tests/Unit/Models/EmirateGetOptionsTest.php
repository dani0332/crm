<?php

use App\Models\Emirate;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function (): void {
    TestSchemaCreator::createMinimalSchema();

    // Ensure a clean slate for each test
    DB::connection('sqlite')->table('emirates')->delete();

    Emirate::factory()->create(['text' => 'Dubai', 'is_active' => 1]);
    Emirate::factory()->create(['text' => 'Abu Dhabi', 'is_active' => 1]);
    Emirate::factory()->create(['text' => 'Sharjah', 'is_active' => 0]);
});

it('returns only active emirates when using withActive', function (): void {
    $options = Emirate::getOptions('id', 'text', true);

    expect($options)->toHaveCount(2);

    $values = $options->pluck('value')->sort()->values()->all();
    $labels = $options->pluck('label')->sort()->values()->all();

    expect($values)->toBe([1, 2]);
    expect($labels)->toBe(['Abu Dhabi', 'Dubai']);
});

it('can include inactive emirates when withActive is false', function (): void {
    $options = Emirate::getOptions('id', 'text', false);

    expect($options)->toHaveCount(3);
    expect($options->pluck('label')->all())->toContain('Sharjah');
});

