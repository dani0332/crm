<?php

namespace Tests\Support;

use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;

class FakePolicyIssuanceProcess
{
    public ?string $completed_step = null;
    public ?string $status = null;
    public int $id = 101;

    public function __construct(public object $model)
    {
    }

    public function update(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function refresh(): self
    {
        return $this;
    }
}

class FakePolicyIssuanceService extends PolicyIssuanceService
{
    public array $logs = [];

    public function storePolicyIssuanceLog(...$arguments): void
    {
        $this->logs[] = $arguments;
    }

    public function updateAPIIssuanceAndInsurerStatus(...$arguments): void
    {
        // no-op for tests
    }
}

class FakeApplicationStorageService
{
    public function getValueByKey(string $key): mixed
    {
        return true;
    }
}

