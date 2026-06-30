<?php

declare(strict_types=1);

use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicBookPolicyService;

test('updateBookingDetails request payload includes through_automation for BookPolicyRequest', function (): void {
    $filename = (new ReflectionClass(DicBookPolicyService::class))->getFileName();
    $lines = file($filename, FILE_IGNORE_NEW_LINES);
    expect($lines)->toBeArray();

    $inUpdateBookingRequestArray = false;
    $foundThroughAutomation = false;

    foreach ($lines as $line) {
        if (preg_match('/^\s*\$updateBookingRequest\s*=\s*\[/', (string) $line) === 1) {
            $inUpdateBookingRequestArray = true;

            continue;
        }

        if (! $inUpdateBookingRequestArray) {
            continue;
        }

        if (str_contains((string) $line, "'through_automation' => true")) {
            $foundThroughAutomation = true;

            break;
        }

        if (preg_match('/^\s*\];\s*$/', (string) $line) === 1) {
            break;
        }
    }

    expect($foundThroughAutomation)->toBeTrue();
});

test('updateBookingDetails and validateBookPolicy snapshot and restore request input around validation', function (): void {
    $filename = (new ReflectionClass(DicBookPolicyService::class))->getFileName();
    $contents = file_get_contents($filename);
    expect($contents)->toBeString();

    expect(substr_count($contents, '$incomingRequestPayload = request()->all();'))->toBe(2);
    expect(substr_count($contents, 'request()->replace($incomingRequestPayload);'))->toBe(2);
});

test('SendBookPolicyDocumentsJob is dispatched on successful Sage booking', function (): void {
    $filename = (new ReflectionClass(DicBookPolicyService::class))->getFileName();
    $contents = file_get_contents($filename);
    expect($contents)->toBeString();

    expect($contents)->toContain('SendBookPolicyDocumentsJob::dispatch(');
});

test('SendBookPolicyDocumentsJob dispatch is inside the Sage success branch only', function (): void {
    $filename = (new ReflectionClass(DicBookPolicyService::class))->getFileName();
    $lines = file($filename, FILE_IGNORE_NEW_LINES);
    expect($lines)->toBeArray();

    $inSuccessBranch = false;
    $dispatchFound = false;
    $braceDepth = 0;

    foreach ($lines as $line) {
        if (str_contains((string) $line, '$createSageProcessResponse[\'status\']')) {
            $inSuccessBranch = true;
        }

        if ($inSuccessBranch) {
            $braceDepth += substr_count((string) $line, '{') - substr_count((string) $line, '}');

            if (str_contains((string) $line, 'SendBookPolicyDocumentsJob::dispatch(')) {
                $dispatchFound = true;

                break;
            }

            // Exiting the success else block without finding the dispatch
            if ($braceDepth < 0) {
                break;
            }
        }
    }

    expect($dispatchFound)->toBeTrue();
});

test('SendBookPolicyDocumentsJob dispatch passes advisor_id quote_id and Travel model_type', function (): void {
    $filename = (new ReflectionClass(DicBookPolicyService::class))->getFileName();
    $contents = file_get_contents($filename);
    expect($contents)->toBeString();

    expect($contents)->toContain("'advisorId' => \$quote->advisor_id,");
    expect($contents)->toContain("'model_type' => quoteTypeCode::Travel,");
    expect($contents)->toContain("'quote_id' => \$quote->id,");
    expect($contents)->toContain('SendBookPolicyDocumentsJob::dispatch($data, $quote->code);');
});
