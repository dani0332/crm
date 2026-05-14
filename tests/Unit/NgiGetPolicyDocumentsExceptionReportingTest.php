<?php

declare(strict_types=1);

use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiGetPolicyDocumentsException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Debug\ShouldntReport;

test('NgiGetPolicyDocumentsException implements ShouldntReport', function () {
    $exception = new NgiGetPolicyDocumentsException('test', NgiGetPolicyDocumentsException::DOCUMENT_DOWNLOAD_FAILED);

    expect($exception)->toBeInstanceOf(ShouldntReport::class);
});

test('NgiGetPolicyDocumentsException is not reported by the exception handler', function () {
    $exception = new NgiGetPolicyDocumentsException('test', NgiGetPolicyDocumentsException::API_CALL_FAILED, ['process_id' => 1]);

    expect(app(ExceptionHandler::class)->shouldReport($exception))->toBeFalse();
});
