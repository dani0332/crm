<?php

declare(strict_types=1);

use App\Services\BaseService;
use App\Services\Reports\ConversionOptimizationReportService;
use Illuminate\Support\Collection;

test('it is decoupled from advisor conversion inheritance', function (): void {
    $parentClass = (new ReflectionClass(ConversionOptimizationReportService::class))
        ->getParentClass()
        ?->getName();

    expect($parentClass)->toBe(BaseService::class);
});

test('it applies ranking averages projections and cap limits to a selected cohort', function () {
    $service = new class(collect(range(1, 10))->mapWithKeys(function (int $advisorId) {
        $maxCapacity = $advisorId === 9 ? 31 : 20;

        return [
            $advisorId => (object) [
                'id' => $advisorId,
                'team_id' => 99,
                'team_name' => 'Organic',
                'sub_team_id' => null,
                'sub_team_name' => null,
                'max_capacity' => $maxCapacity,
            ],
        ];
    })) extends ConversionOptimizationReportService {
        public function __construct(private Collection $advisorMetadata)
        {
            parent::__construct();
        }

        protected function getAdvisorMetadata(array $advisorIds, ?string $lob): Collection
        {
            return $this->advisorMetadata;
        }
    };

    $rows = collect([100, 90, 80, 70, 60, 50, 40, 30, 20, 10])->values()->map(function (int $conversion, int $index) {
        $advisorId = $index + 1;

        return (object) [
            'advisorId' => $advisorId,
            'advisor_name' => "Advisor {$advisorId}",
            'total_leads' => 100,
            'sale_leads' => $conversion,
            'bad_leads' => 0,
            'net_conversion' => $conversion,
        ];
    });

    $result = $service->applyPostQueryCalculations($rows, [
        'lob' => 'Car',
        'teams' => ['1', '2'],
        'cap_percentage' => '20',
    ])->keyBy('advisorId');

    expect($result[1]->ranking)->toBe(1)
        ->and($result[1]->team_average)->toBe(55.0)
        ->and($result[1]->expected_sales)->toBeNull()
        ->and($result[6]->ranking)->toBe(6)
        ->and($result[6]->expected_sales)->toBe(55)
        ->and($result[6]->required_sales)->toBe(5)
        ->and($result[6]->new_conversion)->toBe(55)
        ->and($result[9]->current_cap)->toBe(31)
        ->and($result[9]->suggested_cap)->toBe(7)
        ->and($result[10]->current_cap)->toBe(20)
        ->and($result[10]->suggested_cap)->toBe(0)
        ->and($result[1]->total_average)->toBe(55.0)
        ->and($result[10]->total_average)->toBe(55.0);
});

test('it ranks the full filtered dataset as a single cohort when no team filters are selected', function () {
    $service = new class(collect([1 => (object) ['id' => 1, 'team_id' => 11, 'team_name' => 'Organic', 'sub_team_id' => 101, 'sub_team_name' => 'Value', 'max_capacity' => 20], 2 => (object) ['id' => 2, 'team_id' => 11, 'team_name' => 'Organic', 'sub_team_id' => 101, 'sub_team_name' => 'Value', 'max_capacity' => 20], 3 => (object) ['id' => 3, 'team_id' => null, 'team_name' => null, 'sub_team_id' => null, 'sub_team_name' => null, 'max_capacity' => 20]])) extends ConversionOptimizationReportService
    {
        public function __construct(private Collection $advisorMetadata)
        {
            parent::__construct();
        }

        protected function getAdvisorMetadata(array $advisorIds, ?string $lob): Collection
        {
            return $this->advisorMetadata;
        }
    };

    $rows = collect([
        (object) [
            'advisorId' => 1,
            'advisor_name' => 'Advisor 1',
            'total_leads' => 100,
            'sale_leads' => 50,
            'bad_leads' => 0,
            'net_conversion' => 50,
        ],
        (object) [
            'advisorId' => 2,
            'advisor_name' => 'Advisor 2',
            'total_leads' => 100,
            'sale_leads' => 30,
            'bad_leads' => 0,
            'net_conversion' => 30,
        ],
        (object) [
            'advisorId' => 3,
            'advisor_name' => 'Advisor 3',
            'total_leads' => 100,
            'sale_leads' => 10,
            'bad_leads' => 0,
            'net_conversion' => 10,
        ],
    ]);

    $result = $service->applyPostQueryCalculations($rows, ['lob' => 'Car'])->keyBy('advisorId');

    expect($result[1]->ranking)->toBe(1)
        ->and($result[1]->team_average)->toBe(30.0)
        ->and($result[1]->expected_sales)->toBeNull()
        ->and($result[2]->ranking)->toBe(2)
        ->and($result[2]->team_average)->toBe(30.0)
        ->and($result[2]->expected_sales)->toBeNull()
        ->and($result[3]->ranking)->toBe(3)
        ->and($result[3]->team_average)->toBe(30.0)
        ->and($result[3]->expected_sales)->toBe(30)
        ->and($result[3]->required_sales)->toBe(20)
        ->and($result[3]->new_conversion)->toBe(30)
        ->and($result[1]->total_average)->toBe(30.0)
        ->and($result[3]->total_average)->toBe(30.0);
});

test('it returns rows sorted by conversion descending so ranking matches row order', function () {
    $service = new class(collect([1 => (object) ['id' => 1, 'team_id' => 11, 'team_name' => 'Organic', 'sub_team_id' => null, 'sub_team_name' => null, 'max_capacity' => 20], 2 => (object) ['id' => 2, 'team_id' => 11, 'team_name' => 'Organic', 'sub_team_id' => null, 'sub_team_name' => null, 'max_capacity' => 20], 3 => (object) ['id' => 3, 'team_id' => 11, 'team_name' => 'Organic', 'sub_team_id' => null, 'sub_team_name' => null, 'max_capacity' => 20]])) extends ConversionOptimizationReportService
    {
        public function __construct(private Collection $advisorMetadata)
        {
            parent::__construct();
        }

        protected function getAdvisorMetadata(array $advisorIds, ?string $lob): Collection
        {
            return $this->advisorMetadata;
        }
    };

    $rows = collect([
        (object) [
            'advisorId' => 3,
            'advisor_name' => 'Charlie',
            'quote_batch_id' => 1,
            'batch_name' => 'Batch 1',
            'start_date' => '01-01-2026',
            'end_date' => '07-01-2026',
            'total_leads' => 100,
            'sale_leads' => 10,
            'bad_leads' => 0,
            'net_conversion' => 10.0,
        ],
        (object) [
            'advisorId' => 1,
            'advisor_name' => 'Alice',
            'quote_batch_id' => 1,
            'batch_name' => 'Batch 1',
            'start_date' => '01-01-2026',
            'end_date' => '07-01-2026',
            'total_leads' => 100,
            'sale_leads' => 90,
            'bad_leads' => 0,
            'net_conversion' => 90.0,
        ],
        (object) [
            'advisorId' => 2,
            'advisor_name' => 'Bob',
            'quote_batch_id' => 1,
            'batch_name' => 'Batch 1',
            'start_date' => '01-01-2026',
            'end_date' => '07-01-2026',
            'total_leads' => 100,
            'sale_leads' => 50,
            'bad_leads' => 0,
            'net_conversion' => 50.0,
        ],
    ]);

    $result = $service->applyPostQueryCalculations($rows, [
        'lob' => 'Car',
        'cap_percentage' => '',
    ])->values();

    expect($result)->toHaveCount(3)
        ->and($result[0]->advisorId)->toBe(1)
        ->and($result[0]->conversion)->toBe(90.0)
        ->and($result[0]->ranking)->toBe(1)
        ->and($result[1]->advisorId)->toBe(2)
        ->and($result[1]->conversion)->toBe(50.0)
        ->and($result[1]->ranking)->toBe(2)
        ->and($result[2]->advisorId)->toBe(3)
        ->and($result[2]->conversion)->toBe(10.0)
        ->and($result[2]->ranking)->toBe(3);
});
