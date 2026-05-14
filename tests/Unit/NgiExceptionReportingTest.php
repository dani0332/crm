<?php

declare(strict_types=1);

use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Debug\ShouldntReport;

test('NgiException implements ShouldntReport', function () {
    $exception = new NgiException('test', NgiException::DOCUMENT_DOWNLOAD_FAILED);

    expect($exception)->toBeInstanceOf(ShouldntReport::class);
});

test('NgiException is not reported by the exception handler', function () {
    $exception = new NgiException('test', NgiException::API_CALL_FAILED, ['process_id' => 1]);

    expect(app(ExceptionHandler::class)->shouldReport($exception))->toBeFalse();
});
