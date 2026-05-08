<?php

declare(strict_types=1);

use App\Jobs\Renewals\FetchPlansForRenewalsQuoteJob;
use App\Models\RenewalQuoteProcess;
use App\Models\RenewalStatusProcess;
use Illuminate\Queue\Middleware\WithoutOverlapping;

test('middleware uses WithoutOverlapping with sixty second lock expiry and no release on overlap', function () {
    $renewalQuoteProcess = new RenewalQuoteProcess;
    $renewalQuoteProcess->id = 42;
    $renewalQuoteProcess->policy_number = 'POL-001';
    $renewalQuoteProcess->batch = 'batch-a';

    $renewalStatusProcess = new RenewalStatusProcess;
    $renewalStatusProcess->id = 7;

    $job = new FetchPlansForRenewalsQuoteJob($renewalQuoteProcess, $renewalStatusProcess);
    $middleware = $job->middleware();

    expect($middleware)->toBeArray()
        ->and($middleware)->toHaveCount(1)
        ->and($middleware[0])->toBeInstanceOf(WithoutOverlapping::class);

    /** @var WithoutOverlapping $lock */
    $lock = $middleware[0];

    expect($lock->key)->toBe(42)
        ->and($lock->expiresAfter)->toBe(60)
        ->and($lock->releaseAfter)->toBeNull();
});
