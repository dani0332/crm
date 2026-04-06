<?php

declare(strict_types=1);

use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\QuoteTypeId;
use App\Jobs\WatermarkDocumentsJob;
use App\Models\CarQuote;
use App\Models\DocumentType;
use App\Models\EmbeddedProduct;
use App\Models\EmbeddedProductOption;
use App\Models\EmbeddedTransaction;
use App\Models\QuoteDocument;
use App\Services\Life\EmbeddedProductService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

test('fetchUpdateEpDocument returns false when quote cannot be resolved via getQuoteObject', function () {
    TestSchemaCreator::createMinimalSchema();
    Storage::fake('azureIMPrivate');

    $user = TestDataSeeder::createAdminUser();
    Auth::login($user);

    DocumentType::factory()->createOneQuietly([
        'code' => QuoteDocumentsEnum::EP,
        'quote_type_id' => QuoteTypeId::Car,
        'text' => 'EP Document',
    ]);

    $ep = EmbeddedProduct::factory()->createOneQuietly();
    $option = EmbeddedProductOption::factory()->createOneQuietly(['embedded_product_id' => $ep->id]);

    $orphanQuoteRequestId = 99_999_999;

    $transaction = EmbeddedTransaction::factory()
        ->forProduct($option->id)
        ->createOneQuietly([
            'quote_request_id' => $orphanQuoteRequestId,
            'quote_request_type' => CarQuote::class,
            'quote_type_id' => QuoteTypeId::Car,
            'is_selected' => true,
            'payment_status_id' => PaymentStatusEnum::CAPTURED,
        ]);

    $document = QuoteDocument::factory()->createOneQuietly([
        'quote_documentable_type' => EmbeddedTransaction::class,
        'quote_documentable_id' => $transaction->id,
        'document_type_code' => QuoteDocumentsEnum::EP,
        'doc_name' => 'CERT_suffix.pdf',
    ]);

    $service = app(EmbeddedProductService::class);

    $result = $service->updateEpDocument([
        'epId' => $ep->id,
        'modelType' => 'car',
        'quoteId' => $orphanQuoteRequestId,
        'documentId' => $document->id,
        'documentNumber' => 'DOC-001',
        'file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
        'remarks' => 'Orphan quote_request_id — no CarQuote row',
    ]);

    expect($result)->toBeFalse();
});

test('fetchUpdateEpDocument returns false when document type row is missing for request LOB', function () {
    TestSchemaCreator::createMinimalSchema();
    Storage::fake('azureIMPrivate');

    $user = TestDataSeeder::createAdminUser();
    Auth::login($user);

    $orphanCode = 'EP_'.strtoupper(uniqid());

    DocumentType::factory()->createOneQuietly([
        'code' => $orphanCode,
        'quote_type_id' => QuoteTypeId::Travel,
    ]);

    $ep = EmbeddedProduct::factory()->createOneQuietly();
    $option = EmbeddedProductOption::factory()->createOneQuietly(['embedded_product_id' => $ep->id]);
    $carQuote = CarQuote::factory()->createOneQuietly();

    $transaction = EmbeddedTransaction::factory()
        ->forCarQuote($carQuote)
        ->forProduct($option->id)
        ->createOneQuietly([
            'is_selected' => true,
            'payment_status_id' => PaymentStatusEnum::CAPTURED,
        ]);

    $document = QuoteDocument::factory()->createOneQuietly([
        'quote_documentable_type' => EmbeddedTransaction::class,
        'quote_documentable_id' => $transaction->id,
        'document_type_code' => $orphanCode,
        'doc_name' => 'CERT_suffix.pdf',
    ]);

    $service = app(EmbeddedProductService::class);

    $result = $service->updateEpDocument([
        'epId' => $ep->id,
        'modelType' => 'car',
        'quoteId' => $carQuote->id,
        'documentId' => $document->id,
        'documentNumber' => 'DOC-001',
        'file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
        'remarks' => 'LOB mismatch — metadata exists for travel only',
    ]);

    expect($result)->toBeFalse();
});

test('fetchUpdateEpDocument soft-deletes the old row, persists the new document, and dispatches watermark inside transaction', function () {
    TestSchemaCreator::createMinimalSchema();
    Storage::fake('azureIMPrivate');
    Queue::fake();

    $user = TestDataSeeder::createAdminUser();
    Auth::login($user);

    DocumentType::factory()->createOneQuietly([
        'code' => QuoteDocumentsEnum::EP,
        'quote_type_id' => QuoteTypeId::Car,
        'text' => 'EP Document',
    ]);

    $ep = EmbeddedProduct::factory()->createOneQuietly();
    $option = EmbeddedProductOption::factory()->createOneQuietly(['embedded_product_id' => $ep->id]);
    $carQuote = CarQuote::factory()->createOneQuietly();

    $transaction = EmbeddedTransaction::factory()
        ->forCarQuote($carQuote)
        ->forProduct($option->id)
        ->createOneQuietly([
            'is_selected' => true,
            'payment_status_id' => PaymentStatusEnum::CAPTURED,
        ]);

    $document = QuoteDocument::factory()->createOneQuietly([
        'quote_documentable_type' => EmbeddedTransaction::class,
        'quote_documentable_id' => $transaction->id,
        'document_type_code' => QuoteDocumentsEnum::EP,
        'doc_name' => 'CERT_original.pdf',
    ]);

    $service = app(EmbeddedProductService::class);

    $result = $service->updateEpDocument([
        'epId' => $ep->id,
        'modelType' => 'car',
        'quoteId' => $carQuote->id,
        'documentId' => $document->id,
        'documentNumber' => 'DOC-001',
        'file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
        'remarks' => 'Replacement upload',
    ]);

    expect($result)->toBeTrue();

    $document->refresh();
    expect($document->trashed())->toBeTrue();

    $replacement = QuoteDocument::query()
        ->where('quote_documentable_id', $transaction->id)
        ->where('quote_documentable_type', EmbeddedTransaction::class)
        ->whereNull('deleted_at')
        ->sole();

    expect((bool) $replacement->is_manual_override)->toBeTrue()
        ->and($replacement->override_remarks)->toBe('Replacement upload');

    Queue::assertPushed(WatermarkDocumentsJob::class);
});

test('fetchUpdateEpDocument keeps doc_name prefixed with certificate number for tax invoice documents', function () {
    TestSchemaCreator::createMinimalSchema();
    Storage::fake('azureIMPrivate');
    Queue::fake();

    $user = TestDataSeeder::createAdminUser();
    Auth::login($user);

    DocumentType::factory()->createOneQuietly([
        'code' => QuoteDocumentsEnum::CAR_TAX_INVOICE,
        'quote_type_id' => QuoteTypeId::Car,
        'text' => 'Car Tax Invoice',
    ]);

    $ep = EmbeddedProduct::factory()->createOneQuietly();
    $option = EmbeddedProductOption::factory()->createOneQuietly(['embedded_product_id' => $ep->id]);
    $carQuote = CarQuote::factory()->createOneQuietly();

    $transaction = EmbeddedTransaction::factory()
        ->forCarQuote($carQuote)
        ->forProduct($option->id)
        ->createOneQuietly([
            'is_selected' => true,
            'payment_status_id' => PaymentStatusEnum::CAPTURED,
            'certificate_number' => 'CERT-KEEP-001',
            'tax_invoice_no' => 'TAX-OLD-001',
        ]);

    $document = QuoteDocument::factory()->createOneQuietly([
        'quote_documentable_type' => EmbeddedTransaction::class,
        'quote_documentable_id' => $transaction->id,
        'document_type_code' => QuoteDocumentsEnum::CAR_TAX_INVOICE,
        'doc_name' => 'CERT-KEEP-001_original.pdf',
    ]);

    $service = app(EmbeddedProductService::class);

    $result = $service->updateEpDocument([
        'epId' => $ep->id,
        'modelType' => 'car',
        'quoteId' => $carQuote->id,
        'documentId' => $document->id,
        'documentNumber' => 'TAX-NEW-002',
        'file' => UploadedFile::fake()->create('tax-invoice.pdf', 100, 'application/pdf'),
        'remarks' => 'Tax invoice manual override',
    ]);

    expect($result)->toBeTrue();

    $transaction->refresh();

    expect($transaction->tax_invoice_no)->toBe('TAX-NEW-002')
        ->and($transaction->certificate_number)->toBe('CERT-KEEP-001');

    $replacement = QuoteDocument::query()
        ->where('quote_documentable_id', $transaction->id)
        ->where('quote_documentable_type', EmbeddedTransaction::class)
        ->whereNull('deleted_at')
        ->sole();

    expect($replacement->doc_name)->toBe('CERT-KEEP-001_original.pdf');
});
