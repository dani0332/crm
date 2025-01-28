<?php

namespace App\Services\ProcessTracker;

use App\Enums\ProcessTracker\ProcessTrackerTypeEnum;
use App\Enums\ProcessTracker\StepsEnums\Step;
use Error;
use Illuminate\Support\Str;

class ProcessTrackerService
{
    use ProcessTrackable;

    public function __construct()
    {
        $this->steps = collect();
    }

    public function getProcess()
    {
        return $this->process;
    }

    public function addProcess(ProcessTrackerTypeEnum $processType)
    {
        $this->process = $this->processTracker->startProcess($processType);

        return $this;
    }

    private function validateData(Step $step, array $data = []): void
    {
        $stepData = $step->data();
        $missingFields = array_diff($stepData->schema, array_keys($data));
        if (count($missingFields) > 0) {
            $fields = Str::plural('Field', count($missingFields));
            $isAre = Str::plural('is', count($missingFields));
            $missingFields = implode(',', $missingFields);
            throw new Error("{$fields} {$missingFields} {$isAre} missing from data");
        }
    }

    private function resolveDescriptionAndData(string $description, array $allData, array $removableWords = []): array
    {
        $data = collect($allData);

        $replaceableData = $data->filter(fn ($value, $key) => Str::startsWith($key, ':') || Str::startsWith($key, '@'))->map(function ($value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            if ($value) {
                return "<span class='bg-yellow-300'>{$value}</span>";
            }

            return $value;
        })->toArray();

        $description = str_replace($removableWords, '', $description);
        $description = strtr($description, $replaceableData);
        $description = Str::squish($description);

        $data = $data->filter(fn ($value, $key) => ! Str::startsWith($key, ':'))->mapWithKeys(function ($value, $key) {
            $newKey = ltrim($key, '@');

            return [$newKey => $value];
        })->toArray();

        return [$description, $data];
    }

    public function addStep(Step $step, array $data = [], ?string $stepDescription = null, array $removableWords = []): self
    {
        $this->validateData($step, $data);

        $stepData = $step->data();

        [$description, $filteredData] = $this->resolveDescriptionAndData($stepDescription ?: $stepData?->description, $data, $removableWords);

        $this->steps->push([
            'step_number' => (count($this->steps) + 1),
            'step' => $step,
            'stepData' => $stepData,
            'description' => $description,
            'data' => $filteredData,
            'performedAt' => now()->toIso8601String(),
        ]);

        return $this;
    }

    public function saveResult(
        Step $step,
        ?array $data = [],
        ?string $summary = null,
        array $details = [],
        bool $isSuccess = false,
        bool $ignoreStep = false,
        array $removableWords = []
    ): self {
        if (! $ignoreStep) {
            $this->addStep($step, $data, removableWords: $removableWords);
            [$description] = $this->resolveDescriptionAndData($summary ?: $step->data()?->description, $data, $removableWords);
        } else {
            $description = $summary ?: ($this->steps->last()['description'] ?? $step->data()?->description);
        }

        return $this->saveIteration($isSuccess, $description, $details);
    }

    private function saveIteration(bool $isSuccess, string $summary, array $processAdditionalDetails = []): self
    {
        $this->process->iterations()->create([
            'isSuccess' => $isSuccess,
            'summary' => $summary,
            'performedBy' => auth()->id() ?? null,
            'steps' => $this->steps->toArray(),
            ...$processAdditionalDetails,
        ]);

        return $this;
    }
}
