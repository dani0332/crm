<?php

declare(strict_types=1);

use App\Jobs\ReverseEmbeddedProductOnSageJob;
use App\Models\EmbeddedTransaction;
use App\Models\SageProcess;
use Carbon\Carbon;
use Illuminate\Queue\Middleware\WithoutOverlapping;

test('middleware uses stable WithoutOverlapping key per embedded transaction', function (): void {
    $epTransaction = new EmbeddedTransaction;
    $epTransaction->code = 'EP-TEST-001';

    $sageProcess = new SageProcess;
    $sageProcess->id = 99;

    $sageRequest = (object) ['insurerID' => 1, 'epShortCode' => 'MDX'];
    $request = (object) ['modelType' => 'Car', 'quoteId' => 1, 'quoteTypeId' => 1];

    Carbon::setTestNow('2026-05-20 10:00:00');
    $firstJob = new ReverseEmbeddedProductOnSageJob($sageRequest, $epTransaction, $request, $sageProcess);
    $firstLock = $firstJob->middleware()[0];

    Carbon::setTestNow('2026-05-20 10:15:00');
    $secondJob = new ReverseEmbeddedProductOnSageJob($sageRequest, $epTransaction, $request, $sageProcess);
    $secondLock = $secondJob->middleware()[0];

    Carbon::setTestNow();

    expect($firstLock)->toBeInstanceOf(WithoutOverlapping::class)
        ->and($secondLock)->toBeInstanceOf(WithoutOverlapping::class)
        ->and($firstLock->key)->toBe('EP-TEST-001-reverse')
        ->and($secondLock->key)->toBe('EP-TEST-001-reverse')
        ->and($firstLock->releaseAfter)->toBeNull();
});
