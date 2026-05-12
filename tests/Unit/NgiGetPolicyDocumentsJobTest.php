<?php

declare(strict_types=1);

use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiGetPolicyDocumentsJob;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;

test('implements ShouldBeUnique so only one run per process is in flight', function () {
    $job = new NgiGetPolicyDocumentsJob(42);

    expect($job)->toBeInstanceOf(ShouldBeUnique::class)
        ->and($job)->toBeInstanceOf(ShouldQueue::class)
        ->and($job)->not->toBeInstanceOf(ShouldBeUniqueUntilProcessing::class)
        ->and($job->uniqueId())->toBe('ngi-get-policy-docs-42');
});
