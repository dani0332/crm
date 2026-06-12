<?php

declare(strict_types=1);

use App\Jobs\WatermarkDocumentsJob;
use App\Models\DocumentType;
use App\Models\QuoteDocument;
use App\Services\QuoteDocumentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToCheckExistence;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    Storage::fake('azureIMPrivate');
});

test('handle succeeds and updates quote document when file exists and watermark returns data', function () {
    $documentType = DocumentType::factory()->create(['code' => 'COI']);
    $quoteDocument = QuoteDocument::factory()->create([
        'quote_documentable_id' => 1,
        'doc_url' => 'documents/Car/test_doc.pdf',
        'doc_name' => 'test_doc.pdf',
        'doc_mime_type' => 'application/pdf',
        'document_type_code' => $documentType->code,
    ]);

    Storage::disk('azureIMPrivate')->put($quoteDocument->doc_url, 'content');

    $watermarkedName = 'watermarked_test_doc.pdf';
    $watermarkedUrl = 'documents/Car/watermarked_test_doc.pdf';

    $this->mock(QuoteDocumentService::class, function ($mock) use ($watermarkedName, $watermarkedUrl) {
        $mock->shouldReceive('watermarkPdf')
            ->once()
            ->andReturn([
                'watermarked_doc_name' => $watermarkedName,
                'watermarked_doc_url' => $watermarkedUrl,
            ]);
    });

    $job = new WatermarkDocumentsJob($quoteDocument->id, 'TEST-UUID', $documentType->id);
    $job->handle();

    $quoteDocument->refresh();
    expect($quoteDocument->watermarked_doc_name)->toBe($watermarkedName)
        ->and($quoteDocument->watermarked_doc_url)->toBe($watermarkedUrl);
});

test('handle returns early when file is already being processed', function () {
    $documentType = DocumentType::factory()->create();
    $quoteDocument = QuoteDocument::factory()->create([
        'quote_documentable_id' => 1,
        'doc_url' => 'documents/Car/doc.pdf',
        'doc_mime_type' => 'application/pdf',
    ]);
    Storage::disk('azureIMPrivate')->put($quoteDocument->doc_url, 'content');

    $lockKey = "watermark_{$quoteDocument->id}_TEST-UUID_{$documentType->id}";
    Cache::put("processing_{$lockKey}", true, now()->addMinutes(5));

    $this->mock(QuoteDocumentService::class, function ($mock) {
        $mock->shouldNotReceive('watermarkPdf');
    });

    $job = new WatermarkDocumentsJob($quoteDocument->id, 'TEST-UUID', $documentType->id);
    $job->handle();

    $quoteDocument->refresh();
    expect($quoteDocument->watermarked_doc_name)->toBeNull()
        ->and($quoteDocument->watermarked_doc_url)->toBeNull();
});

test('handle returns early when quote document or document type not found', function () {
    $documentType = DocumentType::factory()->create();
    $quoteDocument = QuoteDocument::factory()->create([
        'quote_documentable_id' => 1,
        'doc_url' => 'documents/Car/doc.pdf',
    ]);

    $nonExistentDocumentId = 999999;
    $job = new WatermarkDocumentsJob($nonExistentDocumentId, 'TEST-UUID', $documentType->id);

    $job->handle();

    expect(QuoteDocument::find($quoteDocument->id))->not->toBeNull()
        ->and(QuoteDocument::find($quoteDocument->id)->watermarked_doc_name)->toBeNull();
});

test('handle returns early when doc_url is empty', function () {
    $documentType = DocumentType::factory()->create();
    $quoteDocument = QuoteDocument::factory()->create([
        'quote_documentable_id' => 1,
        'doc_url' => '',
        'doc_name' => 'doc.pdf',
        'doc_mime_type' => 'application/pdf',
    ]);

    $this->mock(QuoteDocumentService::class, function ($mock) {
        $mock->shouldNotReceive('watermarkPdf');
    });

    $job = new WatermarkDocumentsJob($quoteDocument->id, 'TEST-UUID', $documentType->id);
    $job->handle();

    $quoteDocument->refresh();
    expect($quoteDocument->watermarked_doc_name)->toBeNull();
});

test('handle returns early and clears lock when source file does not exist', function () {
    $documentType = DocumentType::factory()->create();
    $quoteDocument = QuoteDocument::factory()->create([
        'quote_documentable_id' => 1,
        'doc_url' => 'documents/Car/missing.pdf',
        'doc_mime_type' => 'application/pdf',
    ]);
    // Do not put file in Storage so exists() returns false

    $this->mock(QuoteDocumentService::class, function ($mock) {
        $mock->shouldNotReceive('watermarkPdf');
    });

    $job = new WatermarkDocumentsJob($quoteDocument->id, 'TEST-UUID', $documentType->id);
    $job->handle();

    $quoteDocument->refresh();
    expect($quoteDocument->watermarked_doc_name)->toBeNull();

    $lockKey = "watermark_{$quoteDocument->id}_TEST-UUID_{$documentType->id}";
    expect(Cache::has("processing_{$lockKey}"))->toBeFalse();
});

test('handle rethrows exception when watermark service throws', function () {
    $documentType = DocumentType::factory()->create();
    $quoteDocument = QuoteDocument::factory()->create([
        'quote_documentable_id' => 1,
        'doc_url' => 'documents/Car/doc.pdf',
        'doc_mime_type' => 'application/pdf',
    ]);
    Storage::disk('azureIMPrivate')->put($quoteDocument->doc_url, 'content');

    $this->mock(QuoteDocumentService::class, function ($mock) {
        $mock->shouldReceive('watermarkPdf')
            ->once()
            ->andThrow(new Exception('Watermark failed'));
    });

    $job = new WatermarkDocumentsJob($quoteDocument->id, 'TEST-UUID', $documentType->id);

    expect(fn () => $job->handle())->toThrow(Exception::class, 'Watermark failed');

    $quoteDocument->refresh();
    expect($quoteDocument->watermarked_doc_name)->toBeNull();
});

test('handle retries transient azure existence failures before watermarking', function () {
    $documentType = DocumentType::factory()->create(['code' => 'COI']);
    $quoteDocument = QuoteDocument::factory()->create([
        'quote_documentable_id' => 1,
        'doc_url' => 'documents/Car/transient.pdf',
        'doc_name' => 'transient.pdf',
        'doc_mime_type' => 'application/pdf',
        'document_type_code' => $documentType->code,
    ]);

    $storageDisk = Mockery::mock();
    $storageDisk->shouldReceive('exists')
        ->once()
        ->andThrow(UnableToCheckExistence::forLocation(
            $quoteDocument->doc_url,
            new RuntimeException('cURL error 6: Could not resolve host: azstorimprivateprd.blob.core.windows.net')
        ));
    $storageDisk->shouldReceive('exists')
        ->once()
        ->andReturn(true);

    Storage::shouldReceive('disk')
        ->with('azureIMPrivate')
        ->twice()
        ->andReturn($storageDisk);

    $watermarkedName = 'watermarked_transient.pdf';
    $watermarkedUrl = 'documents/Car/watermarked_transient.pdf';

    $this->mock(QuoteDocumentService::class, function ($mock) use ($watermarkedName, $watermarkedUrl) {
        $mock->shouldReceive('watermarkPdf')
            ->once()
            ->andReturn([
                'watermarked_doc_name' => $watermarkedName,
                'watermarked_doc_url' => $watermarkedUrl,
            ]);
    });

    $job = new WatermarkDocumentsJob($quoteDocument->id, 'TEST-UUID', $documentType->id);
    $job->handle();

    $quoteDocument->refresh();
    expect($quoteDocument->watermarked_doc_name)->toBe($watermarkedName)
        ->and($quoteDocument->watermarked_doc_url)->toBe($watermarkedUrl);
});

test('job has correct timeout tries and backoff', function () {
    $job = new WatermarkDocumentsJob(1, 'UUID', 1);

    expect($job->timeout)->toBe(120)
        ->and($job->tries)->toBe(3)
        ->and($job->backoff)->toBe(10);
});

test('middleware returns WithoutOverlapping', function () {
    $job = new WatermarkDocumentsJob(1, 'UUID', 1);
    $middleware = $job->middleware();

    expect($middleware)->toBeArray()->and($middleware)->toHaveCount(1);
});
