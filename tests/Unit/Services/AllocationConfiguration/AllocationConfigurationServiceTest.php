<?php

declare(strict_types=1);

use App\Enums\QuoteTypes;
use App\Models\Allocation\AllocationConfiguration;
use App\Services\AllocationConfiguration\AllocationConfigurationService;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    $this->service = new AllocationConfigurationService;
    $this->userId = TestDataSeeder::createUser(['email' => fake()->unique()->safeEmail()])->id;

    $this->quoteTypeId = DB::connection('sqlite')->table('quote_type')->insertGetId([
        'code' => QuoteTypes::GROUP_MEDICAL->value,
        'short_code' => 'GM',
        'text' => 'Group Medical',
        'is_active' => 1,
        'sort_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

it('creates group medical configuration with auh and non-auh', function () {
    $data = [
        'quote_type_id' => $this->quoteTypeId,
        'quote_type' => QuoteTypes::GROUP_MEDICAL->value,
        'auh' => [
            'micro_brackets' => [
                [
                    'departmentIds' => [1],
                    'employees_min' => 1,
                    'employees_max' => 10,
                    'profiles' => [['advisorIds' => [1], 'planTypeIds' => [1]]],
                ],
            ],
            'non_micro_brackets' => [],
        ],
        'non-auh' => [
            'micro_brackets' => [],
            'non_micro_brackets' => [
                [
                    'departmentIds' => [1],
                    'employees_min' => 11,
                    'employees_max' => 50,
                    'profiles' => [['advisorIds' => [1], 'planTypeIds' => [1]]],
                ],
            ],
        ],
    ];

    $config = $this->service->createConfiguration(QuoteTypes::GROUP_MEDICAL, $data, $this->userId);

    expect($config)->toBeInstanceOf(AllocationConfiguration::class);
    expect($config->quote_type)->toBe(QuoteTypes::GROUP_MEDICAL);
    expect($config->config)->toHaveKeys(['auh', 'non-auh']);
    expect($config->config['auh']['micro_brackets'])->toHaveCount(1);
    expect($config->config['auh']['micro_brackets'][0]['employees_min'])->toBe(1);
    expect($config->config['auh']['micro_brackets'][0]['employees_max'])->toBe(10);
    expect($config->config['non-auh']['non_micro_brackets'])->toHaveCount(1);
    expect($config->config['non-auh']['non_micro_brackets'][0]['employees_min'])->toBe(11);
    expect($config->config['non-auh']['non_micro_brackets'][0]['employees_max'])->toBe(50);
});

it('updates only auh region and preserves non-auh when payload has only auh', function () {
    $existing = AllocationConfiguration::on('sqlite')->create([
        'quote_type_id' => $this->quoteTypeId,
        'quote_type' => QuoteTypes::GROUP_MEDICAL,
        'config' => [
            'auh' => [
                'micro_brackets' => [
                    [
                        'departmentIds' => [1],
                        'employees_min' => 1,
                        'employees_max' => 10,
                        'profiles' => [['advisorIds' => [1], 'planTypeIds' => [1]]],
                    ],
                ],
                'non_micro_brackets' => [],
            ],
            'non-auh' => [
                'micro_brackets' => [],
                'non_micro_brackets' => [
                    [
                        'departmentIds' => [1],
                        'employees_min' => 11,
                        'employees_max' => 100,
                        'profiles' => [['advisorIds' => [1], 'planTypeIds' => [1]]],
                    ],
                ],
            ],
        ],
        'created_by' => $this->userId,
    ]);

    $updateData = [
        'quote_type_id' => $this->quoteTypeId,
        'quote_type' => QuoteTypes::GROUP_MEDICAL->value,
        'auh' => [
            'micro_brackets' => [
                [
                    'departmentIds' => [1],
                    'employees_min' => 1,
                    'employees_max' => 25,
                    'profiles' => [['advisorIds' => [1], 'planTypeIds' => [1]]],
                ],
            ],
            'non_micro_brackets' => [],
        ],
    ];

    $updated = $this->service->updateConfiguration($existing, QuoteTypes::GROUP_MEDICAL, $updateData, $this->userId);

    expect($updated->config['auh']['micro_brackets'][0]['employees_max'])->toBe(25);
    expect($updated->config['non-auh']['non_micro_brackets'])->toHaveCount(1);
    expect($updated->config['non-auh']['non_micro_brackets'][0]['employees_min'])->toBe(11);
    expect($updated->config['non-auh']['non_micro_brackets'][0]['employees_max'])->toBe(100);
});

it('updates only non-auh region and preserves auh when payload has only non-auh', function () {
    $existing = AllocationConfiguration::on('sqlite')->create([
        'quote_type_id' => $this->quoteTypeId,
        'quote_type' => QuoteTypes::GROUP_MEDICAL,
        'config' => [
            'auh' => [
                'micro_brackets' => [
                    [
                        'departmentIds' => [1],
                        'employees_min' => 1,
                        'employees_max' => 10,
                        'profiles' => [['advisorIds' => [1], 'planTypeIds' => [1]]],
                    ],
                ],
                'non_micro_brackets' => [],
            ],
            'non-auh' => [
                'micro_brackets' => [],
                'non_micro_brackets' => [
                    [
                        'departmentIds' => [1],
                        'employees_min' => 11,
                        'employees_max' => 100,
                        'profiles' => [['advisorIds' => [1], 'planTypeIds' => [1]]],
                    ],
                ],
            ],
        ],
        'created_by' => $this->userId,
    ]);

    $updateData = [
        'quote_type_id' => $this->quoteTypeId,
        'quote_type' => QuoteTypes::GROUP_MEDICAL->value,
        'non-auh' => [
            'micro_brackets' => [],
            'non_micro_brackets' => [
                [
                    'departmentIds' => [1],
                    'employees_min' => 11,
                    'employees_max' => 200,
                    'profiles' => [['advisorIds' => [1], 'planTypeIds' => [1]]],
                ],
            ],
        ],
    ];

    $updated = $this->service->updateConfiguration($existing, QuoteTypes::GROUP_MEDICAL, $updateData, $this->userId);

    expect($updated->config['non-auh']['non_micro_brackets'][0]['employees_max'])->toBe(200);
    expect($updated->config['auh']['micro_brackets'])->toHaveCount(1);
    expect($updated->config['auh']['micro_brackets'][0]['employees_max'])->toBe(10);
});

it('clears a bracket list when the payload includes that key with an empty array', function () {
    $existing = AllocationConfiguration::on('sqlite')->create([
        'quote_type_id' => $this->quoteTypeId,
        'quote_type' => QuoteTypes::GROUP_MEDICAL,
        'config' => [
            'auh' => [
                'micro_brackets' => [
                    [
                        'departmentIds' => [1],
                        'employees_min' => 1,
                        'employees_max' => 10,
                        'profiles' => [['advisorIds' => [1], 'planTypeIds' => [1]]],
                    ],
                ],
                'non_micro_brackets' => [
                    [
                        'departmentIds' => [1],
                        'employees_min' => 11,
                        'employees_max' => 20,
                        'profiles' => [['advisorIds' => [1], 'planTypeIds' => [1]]],
                    ],
                ],
            ],
            'non-auh' => [
                'micro_brackets' => [],
                'non_micro_brackets' => [],
            ],
        ],
        'created_by' => $this->userId,
    ]);

    $updateData = [
        'quote_type_id' => $this->quoteTypeId,
        'quote_type' => QuoteTypes::GROUP_MEDICAL->value,
        'auh' => [
            'micro_brackets' => [],
            'non_micro_brackets' => [
                [
                    'departmentIds' => [1],
                    'employees_min' => 11,
                    'employees_max' => 20,
                    'profiles' => [['advisorIds' => [1], 'planTypeIds' => [1]]],
                ],
            ],
        ],
    ];

    $updated = $this->service->updateConfiguration($existing, QuoteTypes::GROUP_MEDICAL, $updateData, $this->userId);

    expect($updated->config['auh']['micro_brackets'])->toBeArray()->toBeEmpty();
    expect($updated->config['auh']['non_micro_brackets'])->toHaveCount(1);
});

it('keeps a bracket list when the region is sent but that bracket key is omitted', function () {
    $existing = AllocationConfiguration::on('sqlite')->create([
        'quote_type_id' => $this->quoteTypeId,
        'quote_type' => QuoteTypes::GROUP_MEDICAL,
        'config' => [
            'auh' => [
                'micro_brackets' => [
                    [
                        'departmentIds' => [1],
                        'employees_min' => 1,
                        'employees_max' => 5,
                        'profiles' => [['advisorIds' => [1], 'planTypeIds' => [1]]],
                    ],
                ],
                'non_micro_brackets' => [
                    [
                        'departmentIds' => [1],
                        'employees_min' => 11,
                        'employees_max' => 20,
                        'profiles' => [['advisorIds' => [1], 'planTypeIds' => [1]]],
                    ],
                ],
            ],
            'non-auh' => [
                'micro_brackets' => [],
                'non_micro_brackets' => [],
            ],
        ],
        'created_by' => $this->userId,
    ]);

    $updateData = [
        'quote_type_id' => $this->quoteTypeId,
        'quote_type' => QuoteTypes::GROUP_MEDICAL->value,
        'auh' => [
            'non_micro_brackets' => [
                [
                    'departmentIds' => [1],
                    'employees_min' => 11,
                    'employees_max' => 30,
                    'profiles' => [['advisorIds' => [1], 'planTypeIds' => [1]]],
                ],
            ],
        ],
    ];

    $updated = $this->service->updateConfiguration($existing, QuoteTypes::GROUP_MEDICAL, $updateData, $this->userId);

    expect($updated->config['auh']['non_micro_brackets'][0]['employees_max'])->toBe(30);
    expect($updated->config['auh']['micro_brackets'])->toHaveCount(1);
    expect($updated->config['auh']['micro_brackets'][0]['employees_max'])->toBe(5);
});

it('preserves the other region when the payload includes both keys as empty arrays like emitData', function () {
    $existing = AllocationConfiguration::on('sqlite')->create([
        'quote_type_id' => $this->quoteTypeId,
        'quote_type' => QuoteTypes::GROUP_MEDICAL,
        'config' => [
            'auh' => [
                'micro_brackets' => [
                    [
                        'departmentIds' => [1],
                        'employees_min' => 1,
                        'employees_max' => 25,
                        'profiles' => [['advisorIds' => [1], 'planTypeIds' => [1]]],
                    ],
                ],
                'non_micro_brackets' => [],
            ],
            'non-auh' => [
                'micro_brackets' => [],
                'non_micro_brackets' => [
                    [
                        'departmentIds' => [1],
                        'employees_min' => 11,
                        'employees_max' => 100,
                        'profiles' => [['advisorIds' => [1], 'planTypeIds' => [1]]],
                    ],
                ],
            ],
        ],
        'created_by' => $this->userId,
    ]);

    $updateData = [
        'quote_type_id' => $this->quoteTypeId,
        'quote_type' => QuoteTypes::GROUP_MEDICAL->value,
        'auh' => [
            'micro_brackets' => [
                [
                    'departmentIds' => [1],
                    'employees_min' => 1,
                    'employees_max' => 30,
                    'profiles' => [['advisorIds' => [1], 'planTypeIds' => [1]]],
                ],
            ],
            'non_micro_brackets' => [],
        ],
        'non-auh' => [
            'micro_brackets' => [],
            'non_micro_brackets' => [],
        ],
    ];

    $updated = $this->service->updateConfiguration($existing, QuoteTypes::GROUP_MEDICAL, $updateData, $this->userId);

    expect($updated->config['auh']['micro_brackets'][0]['employees_max'])->toBe(30);
    expect($updated->config['non-auh']['non_micro_brackets'])->toHaveCount(1);
    expect($updated->config['non-auh']['non_micro_brackets'][0]['employees_max'])->toBe(100);
});
