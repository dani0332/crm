<?php

namespace App\Http\Controllers\V2\Admin;

use App\Enums\ProcessTracker\ProcessTrackerTypeEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Models\ProcessTracker\Tracker;
use App\Models\ProcessTracker\TrackerProcess;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProcessTrackerController extends Controller
{
    public function index()
    {
        [$process, $results] = $this->getProcessData();
        $quoteTypes = $this->getQuoteTypes();

        return inertia('ProcessTracker/Index', [
            'process' => $process,
            'results' => $results,
            'quoteTypes' => $quoteTypes,
        ]);
    }

    private function getQuoteTypes()
    {
        return collect(QuoteTypes::withLabels())->map(function ($item) {
            $processTypes = QuoteTypes::tryFrom($item['value'])?->trackerProcessTypes() ?? [];
            $item['processTypes'] = array_map(function (ProcessTrackerTypeEnum $processType) {
                return [
                    'value' => $processType->value,
                    'label' => $processType->label(),
                ];
            }, $processTypes);

            return $item;
        });
    }

    private function getProcessData()
    {
        $quoteType = QuoteTypes::tryFrom(request('quoteType'));
        $processType = ProcessTrackerTypeEnum::tryFrom(request('processType'));
        $quoteUuid = request('uuid');

        $page = request()->get('page', 1);
        $perPage = 20;

        if ($quoteType && $processType && $quoteUuid) {
            $tracker = Tracker::whereQuoteType($quoteType)->whereQuoteUuid($quoteUuid)->first();
            if ($tracker) {
                $trackerProcess = TrackerProcess::whereBelongsTo($tracker)->whereType($processType)->first();
                if ($trackerProcess) {
                    $iterations = $this->resolveProcessIterations($trackerProcess->iterations);

                    $totalCount = $iterations->count();
                    $iterations = $this->mapIterations($iterations->forPage($page, $perPage));

                    return [
                        $trackerProcess,
                        $this->getPaginator($iterations, $totalCount, $perPage, $page),
                    ];
                }
            }
        }

        return [
            null,
            $this->getPaginator(collect([]), 0, $perPage, $page),
        ];
    }

    private function getPaginator(Collection $iterations, $totalCount, $perPage, $page)
    {
        return new LengthAwarePaginator(
            $iterations,
            $totalCount,
            $perPage,
            $page,
            ['path' => request()->fullUrl()]
        );
    }

    private function resolveProcessIterations($iterations): Collection
    {
        $iterations = collect(($iterations ?? []))->map(function ($iteration) {
            $iteration = collect($iteration);
            $iteration->put('performedAt', Carbon::parse($iteration->get('performedAt', now())));

            return $iteration;
        })->sortByDesc('performedAt');

        [$startDate, $endDate] = $this->getStartAndEndDate();
        if ($startDate) {
            $iterations = $iterations->where('performedAt', '>=', $startDate);
        }
        if ($endDate) {
            $iterations = $iterations->where('performedAt', '<=', $endDate);
        }

        return $iterations;
    }

    private function mapIterations(Collection $iterations): Collection
    {
        return $iterations->map(function ($iteration) {
            $steps = collect($iteration->get('steps'))->map(function ($step) {
                $stepData = ($step['stepData'] ?? []);
                $isDevOnlyStep = $stepData['devOnly'] ?? false;
                $isDataDevOnly = $stepData['dataDevOnly'] ?? false;

                $isHidden = ! auth()->user()->hasRole(RolesEnum::Engineering) && $isDevOnlyStep;
                $isDataHidden = ! auth()->user()->hasRole(RolesEnum::Engineering) && $isDataDevOnly;
                if ($isDataHidden) {
                    unset($step['stepData'], $step['data']);
                }

                return collect([
                    ...$step,
                    'isHidden' => $isHidden,
                ]);
            })->filter(fn ($item) => ! $item->get('isHidden'))->values();
            $iteration->put('steps', $steps);

            return $iteration;
        })->values();
    }

    private function getStartAndEndDate()
    {
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');

        $startDate = request('startEndDate') ?
           Carbon::parse(request('startEndDate')[0])->startOfDay()->format($dateFormat) : null;

        $endDate = request('startEndDate') ?
            Carbon::parse(request('startEndDate')[1])->endOfDay()->format($dateFormat) : null;

        return [$startDate, $endDate];
    }
}
