<?php

declare(strict_types=1);

use App\Enums\QuoteTypes;
use App\Models\Department;
use App\Strategies\Validation\GroupMedicalAllocationValidationStrategy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();

    $this->strategy = new GroupMedicalAllocationValidationStrategy;

    $this->quoteTypeId = DB::connection('sqlite')->table('quote_type')->insertGetId([
        'code' => QuoteTypes::GROUP_MEDICAL->value,
        'short_code' => 'GM',
        'text' => 'Group Medical',
        'is_active' => 1,
        'sort_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->user = TestDataSeeder::createUser(['email' => fake()->unique()->safeEmail()]);
    $this->user->setConnection('sqlite');

    $this->department = new Department;
    $this->department->setConnection('sqlite');
    $this->department->name = 'Test Department';
    $this->department->is_active = 1;
    $this->department->save();
});

it('returns rules for auh and non-auh regions', function () {
    $rules = $this->strategy->getRules();

    expect($rules)->toHaveKeys(['auh', 'non-auh']);
    expect($rules['auh'])->toContain('sometimes', 'array');
    expect($rules['non-auh'])->toContain('sometimes', 'array');
    expect($rules)->toHaveKey('auh.micro_brackets');
    expect($rules)->toHaveKey('non-auh.micro_brackets');
});

it('returns empty validated defaults', function () {
    expect($this->strategy->getValidatedDefaults())->toBe([]);
});

it('passes validation when auh has valid micro bracket with department and profiles', function () {
    $data = [
        'quote_type_id' => $this->quoteTypeId,
        'quote_type' => QuoteTypes::GROUP_MEDICAL->value,
        'auh' => [
            'micro_brackets' => [
                [
                    'departmentIds' => [$this->department->id],
                    'employees_min' => 1,
                    'employees_max' => 10,
                    'profiles' => [
                        [
                            'advisorIds' => [$this->user->id],
                            'planTypeIds' => [1],
                        ],
                    ],
                ],
            ],
            'non_micro_brackets' => [],
        ],
        'non-auh' => [
            'micro_brackets' => [],
            'non_micro_brackets' => [],
        ],
    ];

    $validator = Validator::make($data, $this->strategy->getRules(), $this->strategy->getMessages());
    $validator->passes();
    $this->strategy->validate($validator, $data);

    expect($validator->errors()->isEmpty())->toBeTrue();
});

it('fails validation when no region has any brackets', function () {
    $data = [
        'quote_type_id' => $this->quoteTypeId,
        'quote_type' => QuoteTypes::GROUP_MEDICAL->value,
        'auh' => [
            'micro_brackets' => [],
            'non_micro_brackets' => [],
        ],
        'non-auh' => [
            'micro_brackets' => [],
            'non_micro_brackets' => [],
        ],
    ];

    $validator = Validator::make($data, $this->strategy->getRules(), $this->strategy->getMessages());
    $validator->passes();
    $this->strategy->validate($validator, $data);

    expect($validator->errors()->has('configuration'))->toBeTrue();
    expect($validator->errors()->first('configuration'))->toContain('at least one Micro or Non-Micro bracket');
});

it('fails validation when auh micro bracket has overlapping employee ranges', function () {
    $data = [
        'quote_type_id' => $this->quoteTypeId,
        'quote_type' => QuoteTypes::GROUP_MEDICAL->value,
        'auh' => [
            'micro_brackets' => [
                [
                    'departmentIds' => [$this->department->id],
                    'employees_min' => 1,
                    'employees_max' => 20,
                    'profiles' => [
                        ['advisorIds' => [$this->user->id], 'planTypeIds' => [1]],
                    ],
                ],
                [
                    'departmentIds' => [$this->department->id],
                    'employees_min' => 15,
                    'employees_max' => 30,
                    'profiles' => [
                        ['advisorIds' => [$this->user->id], 'planTypeIds' => [1]],
                    ],
                ],
            ],
            'non_micro_brackets' => [],
        ],
        'non-auh' => [
            'micro_brackets' => [],
            'non_micro_brackets' => [],
        ],
    ];

    $validator = Validator::make($data, $this->strategy->getRules(), $this->strategy->getMessages());
    $validator->passes();
    $this->strategy->validate($validator, $data);

    expect($validator->errors()->isEmpty())->toBeFalse();
    expect($validator->errors()->has('auh.micro_brackets'))->toBeTrue();
    expect($validator->errors()->first('auh.micro_brackets'))->toContain('overlapping');
});

it('fails validation when auh micro bracket has gaps in employee ranges', function () {
    $data = [
        'quote_type_id' => $this->quoteTypeId,
        'quote_type' => QuoteTypes::GROUP_MEDICAL->value,
        'auh' => [
            'micro_brackets' => [
                [
                    'departmentIds' => [$this->department->id],
                    'employees_min' => 1,
                    'employees_max' => 10,
                    'profiles' => [
                        ['advisorIds' => [$this->user->id], 'planTypeIds' => [1]],
                    ],
                ],
                [
                    'departmentIds' => [$this->department->id],
                    'employees_min' => 15,
                    'employees_max' => 30,
                    'profiles' => [
                        ['advisorIds' => [$this->user->id], 'planTypeIds' => [1]],
                    ],
                ],
            ],
            'non_micro_brackets' => [],
        ],
        'non-auh' => [
            'micro_brackets' => [],
            'non_micro_brackets' => [],
        ],
    ];

    $validator = Validator::make($data, $this->strategy->getRules(), $this->strategy->getMessages());
    $validator->passes();
    $this->strategy->validate($validator, $data);

    expect($validator->errors()->isEmpty())->toBeFalse();
    expect($validator->errors()->has('auh.micro_brackets'))->toBeTrue();
    expect($validator->errors()->first('auh.micro_brackets'))->toContain('gaps');
});

it('fails validation when department does not exist', function () {
    $data = [
        'quote_type_id' => $this->quoteTypeId,
        'quote_type' => QuoteTypes::GROUP_MEDICAL->value,
        'auh' => [
            'micro_brackets' => [
                [
                    'departmentIds' => [99999],
                    'employees_min' => 1,
                    'employees_max' => 10,
                    'profiles' => [
                        ['advisorIds' => [$this->user->id], 'planTypeIds' => [1]],
                    ],
                ],
            ],
            'non_micro_brackets' => [],
        ],
        'non-auh' => [
            'micro_brackets' => [],
            'non_micro_brackets' => [],
        ],
    ];

    $validator = Validator::make($data, $this->strategy->getRules(), $this->strategy->getMessages());
    $validator->passes();
    $this->strategy->validate($validator, $data);

    expect($validator->errors()->isEmpty())->toBeFalse();
    expect($validator->errors()->has('auh.micro_brackets.0.departmentIds.0'))->toBeTrue();
});

it('fails validation when advisor does not exist', function () {
    $data = [
        'quote_type_id' => $this->quoteTypeId,
        'quote_type' => QuoteTypes::GROUP_MEDICAL->value,
        'auh' => [
            'micro_brackets' => [
                [
                    'departmentIds' => [$this->department->id],
                    'employees_min' => 1,
                    'employees_max' => 10,
                    'profiles' => [
                        ['advisorIds' => [99999], 'planTypeIds' => [1]],
                    ],
                ],
            ],
            'non_micro_brackets' => [],
        ],
        'non-auh' => [
            'micro_brackets' => [],
            'non_micro_brackets' => [],
        ],
    ];

    $validator = Validator::make($data, $this->strategy->getRules(), $this->strategy->getMessages());

    expect($validator->passes())->toBeFalse();
    expect($validator->errors()->has('auh.micro_brackets.0.profiles.0.advisorIds.0'))->toBeTrue();
});
