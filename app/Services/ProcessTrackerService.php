<?php

namespace App\Services;

use App\Enums\ProcessTrackerTypeEnum;
use App\Models\ProcessTracker;

class ProcessTrackerService
{
    private array $trackerSteps = [];
    private string $uuid;
    private ProcessTrackerTypeEnum $processType;

    public function __construct(string $uuid, ProcessTrackerTypeEnum $processType)
    {
        $this->uuid = $uuid;
        $this->processType = $processType;
    }

    public function addStep(string $stepName, array $data): void
    {
        $this->trackerSteps[] = [
            'step' => $stepName,
            'timestamp' => now(),
            'data' => $data
        ];
        $this->saveTracker();
    }

    private function saveTracker(): void
    {
        ProcessTracker::create([
            'uuid' => $this->uuid,
            'process_type' => $this->processType,
            'tracker_data' => json_encode([
                'uuid' => $this->uuid,
                'process_type' => $this->processType,
                'steps' => $this->trackerSteps
            ])
        ]);
    }
}