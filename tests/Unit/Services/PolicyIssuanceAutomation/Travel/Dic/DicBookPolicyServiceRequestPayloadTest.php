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
