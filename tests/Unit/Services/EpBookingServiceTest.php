<?php

declare(strict_types=1);

use App\Models\DocumentType;
use App\Models\QuoteDocument;
use App\Services\EpBookingService;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToCheckExistence;
use Tests\Helpers\TestSchemaCreator;

class EpBookingServiceTestDouble extends EpBookingService
{
    public function __construct()
    {
        // Tests set only the service state they need and intentionally bypass the protected parent constructor.
    }

    public function withSagePostfix(mixed $documentNumber): mixed
    {
        return self::withSageDocumentNumberPostfix($documentNumber);
    }
}

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

test('watermark document rethrows transient existence failures so the job can retry', function () {
    $documentType = DocumentType::factory()->create(['code' => 'CPS']);
    $quoteDocument = QuoteDocument::factory()->create([
        'quote_documentable_id' => 1,
        'doc_url' => 'documents/car/transient.pdf',
        'doc_name' => 'original_transient.pdf',
        'doc_mime_type' => 'application/pdf',
        'document_type_code' => $documentType->code,
    ]);

    $storageDisk = Mockery::mock();
    $storageDisk->shouldReceive('exists')
        ->times(3)
        ->andThrow(UnableToCheckExistence::forLocation(
            $quoteDocument->doc_url,
            new RuntimeException('cURL error 6: Could not resolve host: azstorimprivateprd.blob.core.windows.net')
        ));

    Storage::shouldReceive('disk')
        ->with('azureIMPrivate')
        ->times(3)
        ->andReturn($storageDisk);

    $service = new EpBookingServiceTestDouble;

    $service->quote = (object) ['uuid' => 'TEST-UUID'];

    expect(fn () => $service->watermarkDocument($quoteDocument, $documentType))
        ->toThrow(RuntimeException::class, "Unable to check existence for: {$quoteDocument->doc_url}");
});

test('sage document number postfix increments between duplicate retries', function (mixed $documentNumber, mixed $expected): void {
    $service = new EpBookingServiceTestDouble;

    expect($service->withSagePostfix($documentNumber))->toBe($expected);
})->with([
    'first duplicate retry' => [
        '108-32',
        '108-32/1',
    ],
    'second duplicate retry' => [
        '108-32/1',
        '108-32/1',
    ],
    'later duplicate retry' => [
        '108-32/9',
        '108-32/9',
    ],
    'empty document number' => [
        '',
        '',
    ],
    'non-string document number' => [
        null,
        null,
    ],
]);
