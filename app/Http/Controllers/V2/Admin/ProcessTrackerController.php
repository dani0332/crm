<?php

namespace App\Http\Controllers\V2\Admin;

use App\Enums\PermissionsEnum;
use App\Enums\ProcessTracker\ProcessTrackerTypeEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Models\ProcessTracker\Tracker;
use App\Models\ProcessTracker\TrackerProcess;
use App\Models\ProcessTracker\TrackerProcessIteration;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class ProcessTrackerController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:'.PermissionsEnum::VIEW_PROCESS_TRACKER], ['only' => ['index']]);
    }

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

        $trackerProcess = null;
        if ($quoteType && $processType && $quoteUuid) {
            $tracker = Tracker::whereQuoteType($quoteType)->whereQuoteUuid($quoteUuid)->first();
            if ($tracker) {
                $trackerProcess = $tracker->processes()->whereType($processType)->first();
                if ($trackerProcess) {
                    $iterationsData = $this->resolveProcessIterations($trackerProcess);

                    return $this->response($trackerProcess, $iterationsData);
                }
            }
        }

        return $this->response($trackerProcess, $this->getPaginator(collect([]), 0, 20, 1));
    }

    private function response(?TrackerProcess $trackerProcess, Paginator|LengthAwarePaginator $iterationsData)
    {
        return [
            $trackerProcess,
            $iterationsData,
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

    private function getStartAndEndDate()
    {
        $dateFormat = config('constants.DB_DATE_FORMAT_MATCH');

        $startDate = request('startEndDate') ?
           Carbon::parse(request('startEndDate')[0])->startOfDay()->format($dateFormat) : null;

        $endDate = request('startEndDate') ?
            Carbon::parse(request('startEndDate')[1])->endOfDay()->format($dateFormat) : null;

        return [$startDate, $endDate];
    }

    private function resolveProcessIterations(TrackerProcess $trackerProcess): Paginator
    {
        $iterations = $trackerProcess->iterations();

        [$startDate, $endDate] = $this->getStartAndEndDate();
        if ($startDate && $endDate) {
            $iterations->whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate);
        }

        $iterations = $iterations->simplePaginate(20)->withQueryString();

        return $this->mapIterations($iterations);

    }

    private function mapIterations(Paginator $iterationsData): Paginator
    {
        $isEngineer = auth()->user()->hasRole(RolesEnum::Engineering);

        $iterationsData->getCollection()->transform(function (TrackerProcessIteration $iteration) use ($isEngineer) {
            $iteration->steps = collect($iteration?->steps ?? [])->map(function ($step) use ($isEngineer) {
                $stepData = $step['stepData'] ?? [];

                $isDevOnlyStep = $stepData['devOnly'] ?? false;
                $isDataDevOnly = $stepData['dataDevOnly'] ?? false;

                $isHidden = ! $isEngineer && $isDevOnlyStep;
                $isDataHidden = ! $isEngineer && $isDataDevOnly;

                if ($isDataHidden) {
                    unset($step['stepData'], $step['data']);
                }

                return collect($step)->put('isHidden', $isHidden);
            })->filter(fn ($item) => ! $item->get('isHidden'))->values();

            return $iteration;
        });

        return $iterationsData;
    }
}
