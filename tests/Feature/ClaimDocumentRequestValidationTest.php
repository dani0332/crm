<?php

declare(strict_types=1);

use App\Http\Requests\ClaimDocumentRequest;
use App\Services\ClaimDocumentService;
use Illuminate\Support\Facades\Validator;

test('validation rule requires files to be an array with at least one item', function () {
    $request = new ClaimDocumentRequest(Mockery::mock(ClaimDocumentService::class));
    $rules = $request->rules();

    expect($rules['files'])->toContain('required')
        ->and($rules['files'])->toContain('array')
        ->and($rules['files'])->toContain('min:1');
});

test('validation fails when files array is empty', function () {
    $validator = Validator::make([
        'files' => [],
        'document_type_code' => 'test-doc',
    ], [
        'files' => ['required', 'array', 'min:1'],
        'document_type_code' => ['required', 'string'],
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('files'))->toBeTrue();
});

test('validation fails when files is missing', function () {
    $validator = Validator::make([
        'document_type_code' => 'test-doc',
    ], [
        'files' => ['required', 'array', 'min:1'],
        'document_type_code' => ['required', 'string'],
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('files'))->toBeTrue();
});

test('validation fails when files is not an array', function () {
    $validator = Validator::make([
        'files' => 'not-an-array',
        'document_type_code' => 'test-doc',
    ], [
        'files' => ['required', 'array', 'min:1'],
        'document_type_code' => ['required', 'string'],
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('files'))->toBeTrue();
});

test('validation passes when files array has one item', function () {
    $validator = Validator::make([
        'files' => ['file1.pdf'],
        'document_type_code' => 'test-doc',
    ], [
        'files' => ['required', 'array', 'min:1'],
        'files.*' => ['required'],
        'document_type_code' => ['required', 'string'],
    ]);

    expect($validator->passes())->toBeTrue();
});

test('validation passes when files array has multiple items', function () {
    $validator = Validator::make([
        'files' => ['file1.pdf', 'file2.jpg'],
        'document_type_code' => 'test-doc',
    ], [
        'files' => ['required', 'array', 'min:1'],
        'files.*' => ['required'],
        'document_type_code' => ['required', 'string'],
    ]);

    expect($validator->passes())->toBeTrue();
});

test('custom error message is set for min validation', function () {
    $request = new ClaimDocumentRequest(Mockery::mock(ClaimDocumentService::class));
    $messages = $request->messages();

    expect($messages)->toHaveKey('files.min')
        ->and($messages['files.min'])->toBe('At least one document must be uploaded.');
});

test('custom error message is set for required validation', function () {
    $request = new ClaimDocumentRequest(Mockery::mock(ClaimDocumentService::class));
    $messages = $request->messages();

    expect($messages)->toHaveKey('files.required')
        ->and($messages['files.required'])->toBe('At least one document is required.');
});

test('custom error message is set for array validation', function () {
    $request = new ClaimDocumentRequest(Mockery::mock(ClaimDocumentService::class));
    $messages = $request->messages();

    expect($messages)->toHaveKey('files.array')
        ->and($messages['files.array'])->toBe('Documents must be provided as an array.');
});
