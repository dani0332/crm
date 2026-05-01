<?php

use App\Http\Requests\ClaimExportValidationRequest;
use Illuminate\Support\Facades\Validator;

// ── Date range – email export (3-month cap) ───────────────────────────────────

it('passes email export validation when date range is exactly 3 months', function () {
    $validator = makeExportValidator([
        'exportType' => 'email',
        'created_at_start' => '2026-01-01',
        'created_at_end' => '2026-04-01',
    ]);

    expect($validator->fails())->toBeFalse();
});

it('passes email export validation when date range is under 3 months', function () {
    $validator = makeExportValidator([
        'exportType' => 'email',
        'created_at_start' => '2026-01-01',
        'created_at_end' => '2026-03-15',
    ]);

    expect($validator->fails())->toBeFalse();
});

it('fails email export validation when date range exceeds 3 months by one day', function () {
    $validator = makeExportValidator([
        'exportType' => 'email',
        'created_at_start' => '2026-01-01',
        'created_at_end' => '2026-04-02',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('created_at_end'))->toBeTrue();
});

it('fails email export validation when date range spans more than 3 months', function () {
    $validator = makeExportValidator([
        'exportType' => 'email',
        'created_at_start' => '2026-01-01',
        'created_at_end' => '2026-06-01',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('created_at_end'))->toBeTrue();
});

// ── Date range – download export (31-day cap) ─────────────────────────────────

it('passes download export validation when date range is exactly 31 days', function () {
    $validator = makeExportValidator([
        'exportType' => 'download',
        'created_at_start' => '2026-01-01',
        'created_at_end' => '2026-02-01',
    ]);

    expect($validator->fails())->toBeFalse();
});

it('fails download export validation when date range exceeds 31 days', function () {
    $validator = makeExportValidator([
        'exportType' => 'download',
        'created_at_start' => '2026-01-01',
        'created_at_end' => '2026-02-03',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('created_at_end'))->toBeTrue();
});

it('passes when no dates are provided', function () {
    $validator = makeExportValidator(['exportType' => 'email']);

    expect($validator->fails())->toBeFalse();
});

it('fails when end date is before start date', function () {
    $validator = makeExportValidator([
        'exportType' => 'download',
        'created_at_start' => '2026-03-01',
        'created_at_end' => '2026-02-01',
    ]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('created_at_end'))->toBeTrue();
});

// ── Export type validation ─────────────────────────────────────────────────────

it('fails with an invalid export type', function () {
    $validator = makeExportValidator(['exportType' => 'fax']);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('exportType'))->toBeTrue();
});

it('passes with a null export type', function () {
    $validator = makeExportValidator(['exportType' => null]);

    expect($validator->fails())->toBeFalse();
});

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Build a validator using ClaimExportValidationRequest rules only (no withValidator hooks).
 * For withValidator (cross-field) logic, use the request's withValidator indirectly
 * by creating the validator and applying the after hooks manually.
 *
 * @param  array<string, mixed>  $data
 */
function makeExportValidator(array $data): Illuminate\Validation\Validator
{
    $request = new ClaimExportValidationRequest;
    $request->merge($data);

    $validator = Validator::make($data, $request->rules(), $request->messages(), $request->attributes());

    $validator->after(function ($v) use ($request) {
        if (! $v->errors()->any()) {
            (new ReflectionMethod($request, 'validateDateRange'))->invoke($request, $v);
        }
    });

    // Trigger validation (passes() runs all rules + after callbacks)
    $validator->passes();

    return $validator;
}
