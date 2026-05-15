<?php

use App\Enums\QuoteStatusEnum;
use App\Services\CRUDService;

// Provide insly defaults so ?-> null-safe access on stdClass doesn't throw
function makeRecord(array $attributes = []): object
{
    return (object) [...['insly_migrated' => null, 'insly_id' => null], ...$attributes];
}

beforeEach(function () {
    $this->service = app(CRUDService::class);
});

// ============================================================================
// Statuses that should ENABLE Send Update
// ============================================================================

it('returns true for PolicyBooked status', function () {
    expect($this->service->hasAtleastOneStatusPolicyIssued(
        makeRecord(['quote_status_id' => QuoteStatusEnum::PolicyBooked])
    ))->toBeTrue();
});

it('returns true for CancellationPending status', function () {
    expect($this->service->hasAtleastOneStatusPolicyIssued(
        makeRecord(['quote_status_id' => QuoteStatusEnum::CancellationPending])
    ))->toBeTrue();
});

it('returns true for PolicyCancelled status', function () {
    expect($this->service->hasAtleastOneStatusPolicyIssued(
        makeRecord(['quote_status_id' => QuoteStatusEnum::PolicyCancelled])
    ))->toBeTrue();
});

it('returns true for PolicyCancelledReissued status', function () {
    expect($this->service->hasAtleastOneStatusPolicyIssued(
        makeRecord(['quote_status_id' => QuoteStatusEnum::PolicyCancelledReissued])
    ))->toBeTrue();
});

// ============================================================================
// Statuses that should BLOCK Send Update (pre-booking states)
// ============================================================================

it('returns false for PolicyIssued status', function () {
    expect($this->service->hasAtleastOneStatusPolicyIssued(
        makeRecord(['quote_status_id' => QuoteStatusEnum::PolicyIssued])
    ))->toBeFalse();
});

it('returns false for PolicySentToCustomer status', function () {
    expect($this->service->hasAtleastOneStatusPolicyIssued(
        makeRecord(['quote_status_id' => QuoteStatusEnum::PolicySentToCustomer])
    ))->toBeFalse();
});

it('returns false for POLICY_BOOKING_QUEUED status', function () {
    expect($this->service->hasAtleastOneStatusPolicyIssued(
        makeRecord(['quote_status_id' => QuoteStatusEnum::POLICY_BOOKING_QUEUED])
    ))->toBeFalse();
});

it('returns false for POLICY_BOOKING_FAILED status', function () {
    expect($this->service->hasAtleastOneStatusPolicyIssued(
        makeRecord(['quote_status_id' => QuoteStatusEnum::POLICY_BOOKING_FAILED])
    ))->toBeFalse();
});

it('returns false when quote_status_id is not set', function () {
    expect($this->service->hasAtleastOneStatusPolicyIssued(makeRecord()))->toBeFalse();
});

// ============================================================================
// Insly migration exceptions (legacy bypass)
// ============================================================================

it('returns true for insly_migrated records regardless of status', function () {
    expect($this->service->hasAtleastOneStatusPolicyIssued(
        makeRecord(['quote_status_id' => QuoteStatusEnum::PolicyIssued, 'insly_migrated' => true])
    ))->toBeTrue();
});

it('returns true for records with insly_id regardless of status', function () {
    expect($this->service->hasAtleastOneStatusPolicyIssued(
        makeRecord(['quote_status_id' => QuoteStatusEnum::PolicyIssued, 'insly_id' => 12345])
    ))->toBeTrue();
});
