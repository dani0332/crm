<?php

declare(strict_types=1);

use App\Services\SageApiService;

it('detects EP duplicate document number Sage messages', function (?string $message, bool $expected): void {
    expect(app(SageApiService::class)->isEPDocumentNumberAlreadyExistsMessage($message))->toBe($expected);
})->with([
    'escaped message' => [
        'Document number \"108-32\" already exists.\n\nEnter a unique number. If the duplicate number was assigned by Accounts Receivable, correct the prefix and sequence numbers for the document type in A/R Options.',
        true,
    ],
    'decoded message' => [
        "Document number \"108-32\" already exists.\n\nEnter a unique number. If the duplicate number was assigned by Accounts Receivable, correct the prefix and sequence numbers for the document type in A/R Options.",
        true,
    ],
    'null message' => [
        null,
        false,
    ],
    'unrelated message' => [
        'Processing conflict: post in progress.',
        false,
    ],
]);
