<?php

use App\Jobs\WatermarkDocumentsJob;
use App\Models\EmbeddedProduct;
use App\Models\QuoteDocument;
use App\Repositories\EmbeddedProductRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Tests\Helpers\TestDataSeeder;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
    // createAdminUser seeds every PermissionsEnum value (including EP_DOCUMENT_MANUAL_OVERRIDE) onto the Admin role.
    $this->user = TestDataSeeder::createAdminUser();
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
});

afterEach(function () {
    Mockery::close();
});

test('unauthorized user cannot call update-ep-document endpoint', function () {
    $unprivilegedUser = TestDataSeeder::createUser(['email' => 'noperm@example.com']);
    $this->actingAs($unprivilegedUser);

    $ep = EmbeddedProduct::factory()->createOneQuietly();
    $doc = QuoteDocument::factory()->createOneQuietly(['quote_documentable_id' => 1]);

    $response = $this->postJson(route('embedded-products.update-ep-document'), [
        'epId' => $ep->id,
        'modelType' => 'car',
        'quoteId' => 1,
        'documentId' => $doc->id,
        'documentNumber' => 'DOC-001',
        'file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
        'remarks' => 'Manual correction',
    ]);

    $response->assertStatus(403);
});

test('authorized user can update ep document', function () {
    Queue::fake();
    $this->actingAs($this->user);

    $ep = EmbeddedProduct::factory()->createOneQuietly();
    $doc = QuoteDocument::factory()->createOneQuietly([
        'quote_documentable_id' => 1,
        'document_type_text' => 'EP Certificate',
    ]);

    // fetchUpdateEpDocument is resolved via app() in the controller, so app->instance() mocking works.
    $mockRepo = Mockery::mock(EmbeddedProductRepository::class)->makePartial();
    $mockRepo->shouldReceive('fetchUpdateEpDocument')
        ->once()
        ->andReturn(true);
    $this->app->instance(EmbeddedProductRepository::class, $mockRepo);

    $response = $this->postJson(route('embedded-products.update-ep-document'), [
        'epId' => $ep->id,
        'modelType' => 'car',
        'quoteId' => 1,
        'documentId' => $doc->id,
        'documentNumber' => 'DOC-001',
        'file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
        'remarks' => 'Manual correction',
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'message' => 'Document updated successfully.']);

    // WatermarkDocumentsJob is dispatched inside fetchUpdateEpDocument which is mocked here,
    // so no job is pushed in this test.
    Queue::assertNotPushed(WatermarkDocumentsJob::class);
});

test('controller returns 422 when repository reports failure', function () {
    Queue::fake();
    $this->actingAs($this->user);

    $ep = EmbeddedProduct::factory()->createOneQuietly();
    $doc = QuoteDocument::factory()->createOneQuietly(['quote_documentable_id' => 1]);

    $mockRepo = Mockery::mock(EmbeddedProductRepository::class)->makePartial();
    $mockRepo->shouldReceive('fetchUpdateEpDocument')
        ->once()
        ->andReturn(false);
    $this->app->instance(EmbeddedProductRepository::class, $mockRepo);

    $response = $this->postJson(route('embedded-products.update-ep-document'), [
        'epId' => $ep->id,
        'modelType' => 'car',
        'quoteId' => 1,
        'documentId' => $doc->id,
        'documentNumber' => 'DOC-001',
        'file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
        'remarks' => 'Manual correction',
    ]);

    $response->assertStatus(422)
        ->assertJson(['success' => false, 'message' => 'Failed to update document.']);
});

test('update-ep-document returns 422 when file is missing', function () {
    $this->actingAs($this->user);

    $ep = EmbeddedProduct::factory()->createOneQuietly();
    $doc = QuoteDocument::factory()->createOneQuietly(['quote_documentable_id' => 1]);

    $response = $this->postJson(route('embedded-products.update-ep-document'), [
        'epId' => $ep->id,
        'modelType' => 'car',
        'quoteId' => 1,
        'documentId' => $doc->id,
        'documentNumber' => 'DOC-001',
        'remarks' => 'Missing file',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['file']);
});

test('update-ep-document returns 422 when remarks is missing', function () {
    $this->actingAs($this->user);

    $ep = EmbeddedProduct::factory()->createOneQuietly();
    $doc = QuoteDocument::factory()->createOneQuietly(['quote_documentable_id' => 1]);

    $response = $this->postJson(route('embedded-products.update-ep-document'), [
        'epId' => $ep->id,
        'modelType' => 'car',
        'quoteId' => 1,
        'documentId' => $doc->id,
        'documentNumber' => 'DOC-001',
        'file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['remarks']);
});

test('update-ep-document returns 422 when documentId does not exist', function () {
    $this->actingAs($this->user);

    $ep = EmbeddedProduct::factory()->createOneQuietly();

    $response = $this->postJson(route('embedded-products.update-ep-document'), [
        'epId' => $ep->id,
        'modelType' => 'car',
        'quoteId' => 1,
        'documentId' => 999999,
        'documentNumber' => 'DOC-001',
        'file' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
        'remarks' => 'Non-existent doc',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['documentId']);
});
