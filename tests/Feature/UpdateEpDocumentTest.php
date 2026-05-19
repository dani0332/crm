<?php

use App\Enums\AuthGuardEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Jobs\WatermarkDocumentsJob;
use App\Models\CarQuote;
use App\Models\EmbeddedProduct;
use App\Models\EmbeddedProductOption;
use App\Models\EmbeddedTransaction;
use App\Models\QuoteDocument;
use App\Services\EmbeddedTransactionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

/**
 * @return array{ep: EmbeddedProduct, carQuote: CarQuote, document: QuoteDocument, transaction: EmbeddedTransaction}
 */
function createValidCarEpDocumentOverrideFixture(): array
{
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
        'document_type_text' => 'EP Certificate',
    ]);

    return ['ep' => $ep, 'carQuote' => $carQuote, 'document' => $document, 'transaction' => $transaction];
}

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    // createAdminUser seeds every PermissionsEnum value (including EP_DOCUMENT_MANUAL_OVERRIDE) onto the Admin role.
    $this->user = TestDataSeeder::createAdminUser();
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
});

afterEach(function () {
    Mockery::close();
});

test('user with only EP_DOCUMENT_MANUAL_OVERRIDE can call update-ep-document without embedded product config', function () {
    Queue::fake();

    $user = TestDataSeeder::createUser(['email' => 'ep-doc-override-only@example.com']);
    $permission = Permission::firstOrCreate(
        ['name' => PermissionsEnum::EP_DOCUMENT_MANUAL_OVERRIDE, 'guard_name' => AuthGuardEnum::Web->value],
        ['created_at' => now(), 'updated_at' => now()]
    );
    $user->givePermissionTo($permission);
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $user->refresh();

    expect($user->can(PermissionsEnum::EMBEDDED_PRODUCT_CONFIG))->toBeFalse();

    $this->actingAs($user);

    $fixture = createValidCarEpDocumentOverrideFixture();

    $mockService = Mockery::mock(EmbeddedTransactionService::class)->makePartial();
    $mockService->shouldReceive('updateEpDocument')
        ->once()
        ->andReturn(true);
    $this->app->instance(EmbeddedTransactionService::class, $mockService);

    $response = $this->postJson(route('embedded-products.update-ep-document'), [
        'epId' => $fixture['ep']->id,
        'modelType' => 'car',
        'quoteId' => $fixture['carQuote']->id,
        'documentId' => $fixture['document']->id,
        'documentNumber' => 'DOC-001',
        'file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
        'remarks' => 'Manual correction',
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'message' => 'Document updated successfully.']);
});

test('unauthorized user cannot call update-ep-document endpoint', function () {
    $unprivilegedUser = TestDataSeeder::createUser(['email' => 'noperm@example.com']);
    $this->actingAs($unprivilegedUser);

    $fixture = createValidCarEpDocumentOverrideFixture();

    $response = $this->postJson(route('embedded-products.update-ep-document'), [
        'epId' => $fixture['ep']->id,
        'modelType' => 'car',
        'quoteId' => $fixture['carQuote']->id,
        'documentId' => $fixture['document']->id,
        'documentNumber' => 'DOC-001',
        'file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
        'remarks' => 'Manual correction',
    ]);

    $response->assertStatus(403);
});

test('authorized user can update ep document', function () {
    Queue::fake();
    $this->actingAs($this->user);

    $fixture = createValidCarEpDocumentOverrideFixture();

    // EmbeddedTransactionService is constructor-injected; app->instance() supplies the mock.
    $mockService = Mockery::mock(EmbeddedTransactionService::class)->makePartial();
    $mockService->shouldReceive('updateEpDocument')
        ->once()
        ->andReturn(true);
    $this->app->instance(EmbeddedTransactionService::class, $mockService);

    $response = $this->postJson(route('embedded-products.update-ep-document'), [
        'epId' => $fixture['ep']->id,
        'modelType' => 'car',
        'quoteId' => $fixture['carQuote']->id,
        'documentId' => $fixture['document']->id,
        'documentNumber' => 'DOC-001',
        'file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
        'remarks' => 'Manual correction',
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'message' => 'Document updated successfully.']);

    // WatermarkDocumentsJob is dispatched inside EmbeddedTransactionService::updateEpDocument, which is mocked here,
    // so no job is pushed in this test.
    Queue::assertNotPushed(WatermarkDocumentsJob::class);
});

test('controller returns 422 when service reports failure', function () {
    Queue::fake();
    $this->actingAs($this->user);

    $fixture = createValidCarEpDocumentOverrideFixture();

    $mockService = Mockery::mock(EmbeddedTransactionService::class)->makePartial();
    $mockService->shouldReceive('updateEpDocument')
        ->once()
        ->andReturn(false);
    $this->app->instance(EmbeddedTransactionService::class, $mockService);

    $response = $this->postJson(route('embedded-products.update-ep-document'), [
        'epId' => $fixture['ep']->id,
        'modelType' => 'car',
        'quoteId' => $fixture['carQuote']->id,
        'documentId' => $fixture['document']->id,
        'documentNumber' => 'DOC-001',
        'file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
        'remarks' => 'Manual correction',
    ]);

    $response->assertStatus(422)
        ->assertJson(['success' => false, 'message' => 'Failed to update document.']);
});

test('update-ep-document returns 422 when modelType is not an allowed embedded-product LOB', function () {
    $this->actingAs($this->user);

    $fixture = createValidCarEpDocumentOverrideFixture();

    $mockService = Mockery::mock(EmbeddedTransactionService::class)->makePartial();
    $mockService->shouldNotReceive('updateEpDocument');
    $this->app->instance(EmbeddedTransactionService::class, $mockService);

    $response = $this->postJson(route('embedded-products.update-ep-document'), [
        'epId' => $fixture['ep']->id,
        'modelType' => 'health',
        'quoteId' => $fixture['carQuote']->id,
        'documentId' => $fixture['document']->id,
        'documentNumber' => 'DOC-001',
        'file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
        'remarks' => 'Invalid LOB',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['modelType']);
});

test('update-ep-document returns 422 when file is missing', function () {
    $this->actingAs($this->user);

    $fixture = createValidCarEpDocumentOverrideFixture();

    $response = $this->postJson(route('embedded-products.update-ep-document'), [
        'epId' => $fixture['ep']->id,
        'modelType' => 'car',
        'quoteId' => $fixture['carQuote']->id,
        'documentId' => $fixture['document']->id,
        'documentNumber' => 'DOC-001',
        'remarks' => 'Missing file',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

test('update-ep-document returns 422 when remarks is missing', function () {
    $this->actingAs($this->user);

    $fixture = createValidCarEpDocumentOverrideFixture();

    $response = $this->postJson(route('embedded-products.update-ep-document'), [
        'epId' => $fixture['ep']->id,
        'modelType' => 'car',
        'quoteId' => $fixture['carQuote']->id,
        'documentId' => $fixture['document']->id,
        'documentNumber' => 'DOC-001',
        'file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['remarks']);
});

test('update-ep-document returns 422 when documentId does not exist', function () {
    $this->actingAs($this->user);

    $fixture = createValidCarEpDocumentOverrideFixture();

    $response = $this->postJson(route('embedded-products.update-ep-document'), [
        'epId' => $fixture['ep']->id,
        'modelType' => 'car',
        'quoteId' => $fixture['carQuote']->id,
        'documentId' => 999999,
        'documentNumber' => 'DOC-001',
        'file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
        'remarks' => 'Non-existent doc',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['documentId']);
});

test('update-ep-document returns 422 when documentId is soft-deleted', function () {
    $this->actingAs($this->user);

    $fixture = createValidCarEpDocumentOverrideFixture();
    $fixture['document']->delete();

    $response = $this->postJson(route('embedded-products.update-ep-document'), [
        'epId' => $fixture['ep']->id,
        'modelType' => 'car',
        'quoteId' => $fixture['carQuote']->id,
        'documentId' => $fixture['document']->id,
        'documentNumber' => 'DOC-001',
        'file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
        'remarks' => 'Previously replaced document',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['documentId']);
});

test('update-ep-document returns 422 when documentId belongs to another quote', function () {
    Queue::fake();
    $this->actingAs($this->user);

    $fixtureA = createValidCarEpDocumentOverrideFixture();
    $carQuoteB = CarQuote::factory()->createOneQuietly();
    $transactionB = EmbeddedTransaction::factory()
        ->forCarQuote($carQuoteB)
        ->forProduct($fixtureA['transaction']->product_id)
        ->createOneQuietly([
            'is_selected' => true,
            'payment_status_id' => PaymentStatusEnum::CAPTURED,
        ]);
    $documentOnOtherQuote = QuoteDocument::factory()->createOneQuietly([
        'quote_documentable_type' => EmbeddedTransaction::class,
        'quote_documentable_id' => $transactionB->id,
        'document_type_code' => QuoteDocumentsEnum::EP,
        'document_type_text' => 'EP Certificate',
    ]);

    $mockService = Mockery::mock(EmbeddedTransactionService::class)->makePartial();
    $mockService->shouldNotReceive('updateEpDocument');
    $this->app->instance(EmbeddedTransactionService::class, $mockService);

    $response = $this->postJson(route('embedded-products.update-ep-document'), [
        'epId' => $fixtureA['ep']->id,
        'modelType' => 'car',
        'quoteId' => $fixtureA['carQuote']->id,
        'documentId' => $documentOnOtherQuote->id,
        'documentNumber' => 'DOC-001',
        'file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
        'remarks' => 'Wrong quote document',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['documentId']);
});
