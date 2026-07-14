<?php

declare(strict_types=1);

use App\Models\DocumentType;
use App\Models\EmbeddedProduct;
use App\Models\EmbeddedProductOption;
use App\Models\EmbeddedTransaction;
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

test('duplicate invoice handling writes adjusted tax_invoice_no to EP table', function () {
    TestSchemaCreator::createMinimalSchema();

    $transaction = EmbeddedTransaction::factory()->createOneQuietly([
        'code' => 'ET-DUPTEST01',
        'tax_invoice_no' => 'INV-001',
        'tax_invoice_buyer_no' => 'INV-002',
    ]);

    $result = EpBookingService::updateInsurerRequestResponseDocumentNumberForSageBooking($transaction, 'INV-001');

    expect($result)->toBeTrue();

    $transaction->refresh();
    expect($transaction->tax_invoice_no)->toBe('INV-001/1');
    expect($transaction->tax_invoice_buyer_no)->toBe('INV-002'); // unchanged
});

test('duplicate invoice handling writes adjusted tax_invoice_buyer_no to EP table', function () {
    TestSchemaCreator::createMinimalSchema();

    $transaction = EmbeddedTransaction::factory()->createOneQuietly([
        'code' => 'ET-DUPTEST02',
        'tax_invoice_no' => 'INV-001',
        'tax_invoice_buyer_no' => 'INV-002',
    ]);

    $result = EpBookingService::updateInsurerRequestResponseDocumentNumberForSageBooking($transaction, 'INV-002');

    expect($result)->toBeTrue();

    $transaction->refresh();
    expect($transaction->tax_invoice_no)->toBe('INV-001'); // unchanged
    expect($transaction->tax_invoice_buyer_no)->toBe('INV-002/1');
});

test('duplicate invoice handling returns false when no invoice number matches the duplicate', function () {
    TestSchemaCreator::createMinimalSchema();

    $transaction = EmbeddedTransaction::factory()->createOneQuietly([
        'code' => 'ET-DUPTEST03',
        'tax_invoice_no' => 'INV-001',
        'tax_invoice_buyer_no' => 'INV-002',
    ]);

    $result = EpBookingService::updateInsurerRequestResponseDocumentNumberForSageBooking($transaction, 'INV-999');

    expect($result)->toBeFalse();

    $transaction->refresh();
    expect($transaction->tax_invoice_no)->toBe('INV-001');
    expect($transaction->tax_invoice_buyer_no)->toBe('INV-002');
});

test('duplicate invoice handling returns false when invoice number already has postfix', function () {
    TestSchemaCreator::createMinimalSchema();

    $transaction = EmbeddedTransaction::factory()->createOneQuietly([
        'code' => 'ET-DUPTEST04',
        'tax_invoice_no' => 'INV-001/1',
        'tax_invoice_buyer_no' => 'INV-002',
    ]);

    $result = EpBookingService::updateInsurerRequestResponseDocumentNumberForSageBooking($transaction, 'INV-001/1');

    expect($result)->toBeFalse();

    $transaction->refresh();
    expect($transaction->tax_invoice_no)->toBe('INV-001/1'); // unchanged
});

test('endsWith match updates invoice number for MDX product', function () {
    $embeddedProduct = EmbeddedProduct::factory()->mdx()->create();
    $productOption = EmbeddedProductOption::factory()->forEmbeddedProduct($embeddedProduct->id)->create();

    $transaction = EmbeddedTransaction::factory()->forProduct($productOption->id)->createOneQuietly([
        'code' => 'ET-MDXTEST01',
        'tax_invoice_no' => 'INV-001',
        'tax_invoice_buyer_no' => 'INV-002',
    ]);

    $result = EpBookingService::updateInsurerRequestResponseDocumentNumberForSageBooking($transaction, '001');

    expect($result)->toBeTrue();

    $transaction->refresh();
    expect($transaction->tax_invoice_no)->toBe('INV-001/1');
    expect($transaction->tax_invoice_buyer_no)->toBe('INV-002');
});

test('endsWith match updates buyer invoice number for RDX product', function () {
    $embeddedProduct = EmbeddedProduct::factory()->rdx()->create();
    $productOption = EmbeddedProductOption::factory()->forEmbeddedProduct($embeddedProduct->id)->create();

    $transaction = EmbeddedTransaction::factory()->forProduct($productOption->id)->createOneQuietly([
        'code' => 'ET-RDXTEST01',
        'tax_invoice_no' => 'INV-001',
        'tax_invoice_buyer_no' => 'INV-002',
    ]);

    $result = EpBookingService::updateInsurerRequestResponseDocumentNumberForSageBooking($transaction, '002');

    expect($result)->toBeTrue();

    $transaction->refresh();
    expect($transaction->tax_invoice_no)->toBe('INV-001');
    expect($transaction->tax_invoice_buyer_no)->toBe('INV-002/1');
});

test('endsWith match is skipped for non-MDX non-RDX products', function () {
    $embeddedProduct = EmbeddedProduct::factory()->ecb()->create();
    $productOption = EmbeddedProductOption::factory()->forEmbeddedProduct($embeddedProduct->id)->create();

    $transaction = EmbeddedTransaction::factory()->forProduct($productOption->id)->createOneQuietly([
        'code' => 'ET-ECBTEST01',
        'tax_invoice_no' => 'INV-001',
        'tax_invoice_buyer_no' => 'INV-002',
    ]);

    $result = EpBookingService::updateInsurerRequestResponseDocumentNumberForSageBooking($transaction, '001');

    expect($result)->toBeFalse();

    $transaction->refresh();
    expect($transaction->tax_invoice_no)->toBe('INV-001');
    expect($transaction->tax_invoice_buyer_no)->toBe('INV-002');
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
