<?php

use App\Enums\AssignmentTypeEnum;
use App\Http\Controllers\V2\AmtController;
use Illuminate\Http\Request;

/**
 * Must stay aligned with the first default created-at filter in
 * {@see AmtController::index()}.
 */
function amtIndexRequestQualifiesForDefaultBqrCreatedTodayFilter(Request $request): bool
{
    return ! isset($request->code) && ! isset($request->email) && ! isset($request->mobile_no) && ! isset($request->created_at_start) && ! isset($request->payment_due_date) && ! isset($request->booking_date) && ! isset($request->company_name) && ! isset($request->insurer_tax_invoice_number) && ! isset($request->insurer_commission_tax_invoice_number);
}

it('qualifies for default bqr.created_at today range when only assignment_type is set', function () {
    $request = Request::create('/medical/amt', 'GET', [
        'assignment_type' => (string) AssignmentTypeEnum::SYSTEM_ASSIGNED,
    ]);

    expect(amtIndexRequestQualifiesForDefaultBqrCreatedTodayFilter($request))->toBeTrue();
});

it('qualifies for default bqr.created_at today range on an empty search request', function () {
    $request = Request::create('/medical/amt', 'GET', []);

    expect(amtIndexRequestQualifiesForDefaultBqrCreatedTodayFilter($request))->toBeTrue();
});

it('does not qualify for default bqr.created_at today range when code is set', function () {
    $request = Request::create('/medical/amt', 'GET', [
        'code' => 'GM-1001',
        'assignment_type' => (string) AssignmentTypeEnum::SYSTEM_ASSIGNED,
    ]);

    expect(amtIndexRequestQualifiesForDefaultBqrCreatedTodayFilter($request))->toBeFalse();
});

it('does not qualify for default bqr.created_at today range when created_at_start is set', function () {
    $request = Request::create('/medical/amt', 'GET', [
        'created_at_start' => '2024-01-01',
    ]);

    expect(amtIndexRequestQualifiesForDefaultBqrCreatedTodayFilter($request))->toBeFalse();
});
