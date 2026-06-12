<?php

declare(strict_types=1);

/**
 * Tests for POST /api/v1/quotes/{quoteType}/documents
 * Sample ref: quote_uuid X85DUBM9, document_type_code SAV_PP
 * File can be sent as binary (multipart) or base64 (is_base_64=1).
 */
use App\Models\DocumentType;
use Illuminate\Http\UploadedFile;
use Tests\Helpers\TestSchemaCreator;

beforeEach(function () {
    TestSchemaCreator::createMinimalSchema();
});

describe('Quote document upload API validation', function () {
    test('fails with 422 when quote_uuid is missing and returns validation message', function () {
        $documentType = DocumentType::factory()->create([
            'code' => 'SAV_PP',
            'text' => 'Savings Plan Document',
            'is_active' => 1,
            'accepted_files' => '.pdf',
            'max_size' => 5120,
            'max_files' => 1,
        ]);
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $response = $this->withHeaders(['Accept' => 'application/json'])->post('/api/v1/quotes/savings/documents', [
            'quoteType' => 'savings',
            'document_type_code' => $documentType->code,
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quote_uuid']);
        expect($response->json('errors.quote_uuid'))->not->toBeEmpty();
    });

    test('fails with 422 when document_type_code is missing and returns validation message', function () {
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $response = $this->withHeaders(['Accept' => 'application/json'])->post('/api/v1/quotes/savings/documents', [
            'quoteType' => 'savings',
            'quote_uuid' => 'X85DUBM9',
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document_type_code']);
        expect($response->json('errors.document_type_code'))->not->toBeEmpty();
    });

    test('fails with 422 when file is missing and returns validation message', function () {
        $documentType = DocumentType::factory()->create([
            'code' => 'SAV_PP',
            'is_active' => 1,
        ]);

        $response = $this->postJson('/api/v1/quotes/savings/documents', [
            'quoteType' => 'savings',
            'quote_uuid' => 'X85DUBM9',
            'document_type_code' => $documentType->code,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
        expect($response->json('errors.file'))->not->toBeEmpty();
    });

    test('fails with 422 when document_type_code does not exist and returns validation message', function () {
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $response = $this->withHeaders(['Accept' => 'application/json'])->post('/api/v1/quotes/savings/documents', [
            'quoteType' => 'savings',
            'quote_uuid' => 'X85DUBM9',
            'document_type_code' => 'INVALID_CODE',
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document_type_code']);
        expect($response->json('errors.document_type_code'))->not->toBeEmpty();
    });

    test('fails with 422 when document_type_code exists but is inactive', function () {
        $documentType = DocumentType::factory()->inactive()->create([
            'code' => 'SAV_PP_INACTIVE',
            'text' => 'Inactive doc type',
            'accepted_files' => '.pdf',
            'max_size' => 5120,
        ]);
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $response = $this->withHeaders(['Accept' => 'application/json'])->post('/api/v1/quotes/savings/documents', [
            'quoteType' => 'savings',
            'quote_uuid' => 'X85DUBM9',
            'document_type_code' => $documentType->code,
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document_type_code']);
    });

    test('fails with 422 when quote type or uuid is invalid and returns type error message', function () {
        $documentType = DocumentType::factory()->create([
            'code' => 'SAV_PP',
            'text' => 'Savings Plan Document',
            'is_active' => 1,
            'accepted_files' => '.pdf',
            'max_size' => 5120,
            'max_files' => 1,
        ]);
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $response = $this->withHeaders(['Accept' => 'application/json'])->post('/api/v1/quotes/savings/documents', [
            'quoteType' => 'savings',
            'quote_uuid' => 'X85DUBM9',
            'document_type_code' => $documentType->code,
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $errors = $response->json('errors');
        expect($errors)->toHaveKey('type');
        expect($errors['type'][0] ?? null)->toContain('Invalid quote type or uuid');
    });
});

describe('Quote document upload API - binary file validation', function () {
    test('fails with 422 when binary file has wrong mime type and returns validation message', function () {
        $documentType = DocumentType::factory()->create([
            'code' => 'SAV_PP',
            'text' => 'Savings Plan Document',
            'is_active' => 1,
            'accepted_files' => '.pdf',
            'max_size' => 5120,
        ]);
        $file = UploadedFile::fake()->create('doc.txt', 100, 'text/plain');

        $response = $this->withHeaders(['Accept' => 'application/json'])->post('/api/v1/quotes/savings/documents', [
            'quoteType' => 'savings',
            'quote_uuid' => 'X85DUBM9',
            'document_type_code' => $documentType->code,
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
        expect($response->json('errors.file'))->not->toBeEmpty();
    });
});

describe('Quote document upload API - base64 file validation', function () {
    test('fails with 422 when is_base_64 is 1 but file is invalid base64 or invalid quote', function () {
        $documentType = DocumentType::factory()->create([
            'code' => 'SAV_PP',
            'text' => 'Savings Plan Document',
            'is_active' => 1,
            'accepted_files' => '.pdf',
            'max_size' => 5,
        ]);

        $response = $this->postJson('/api/v1/quotes/savings/documents', [
            'quoteType' => 'savings',
            'quote_uuid' => 'X85DUBM9',
            'document_type_code' => $documentType->code,
            'is_base_64' => 1,
            'file' => 'not-valid-base64!!!',
        ]);

        $response->assertStatus(422);
        $errors = $response->json('errors');
        expect($errors)->not->toBeEmpty();
        expect(array_keys($errors))->toContain('type');
    });

    test('fails with 422 when is_base_64 is 1 and base64 has wrong file type and returns file type error', function () {
        $documentType = DocumentType::factory()->create([
            'code' => 'SAV_PP',
            'text' => 'Savings Plan Document',
            'is_active' => 1,
            'accepted_files' => '.pdf',
            'max_size' => 5,
        ]);
        $base64Txt = 'data:text/plain;base64,'.base64_encode('plain text content');

        $response = $this->postJson('/api/v1/quotes/savings/documents', [
            'quoteType' => 'savings',
            'quote_uuid' => 'X85DUBM9',
            'document_type_code' => $documentType->code,
            'is_base_64' => 1,
            'file' => $base64Txt,
        ]);

        $response->assertStatus(422);
        $errors = $response->json('errors');
        expect($errors)->not->toBeEmpty();
        if (isset($errors['file'])) {
            expect(implode(' ', $errors['file']))->toContain('file of type');
        }
        if (isset($errors['type'])) {
            expect(implode(' ', $errors['type']))->toContain('Invalid quote type or uuid');
        }
    });
});

describe('Quote document upload API - index documents', function () {
    test('GET documents returns 404 when quote not found', function () {
        $response = $this->getJson('/api/v1/quotes/savings/X85DUBM9/documents');

        $response->assertStatus(404)
            ->assertJson(['message' => 'Quote not found.']);
    });
});
