<?php

it('includes Sage booking wording when isSageBooking is true', function (): void {
    $html = view('email.ep-booking-job-failed', [
        'refId' => 'REF-123',
        'epProductName' => 'TripShield',
        'imcrmLink' => 'https://example.com/q',
        'isSageBooking' => true,
    ])->render();

    expect($html)->toContain('the Sage booking of Embedded Product - TripShield has failed for RefID: REF-123');
});

it('uses generic embedded product failure wording when isSageBooking is false', function (): void {
    $html = view('email.ep-booking-job-failed', [
        'refId' => 'REF-123',
        'epProductName' => 'TripShield',
        'imcrmLink' => 'https://example.com/q',
        'isSageBooking' => false,
    ])->render();

    expect($html)->toContain('Embedded Product - TripShield has failed for RefID: REF-123');
    expect($html)->not->toContain('Sage booking');
});

it('defaults to generic wording when isSageBooking is omitted', function (): void {
    $html = view('email.ep-booking-job-failed', [
        'refId' => 'REF-123',
        'epProductName' => 'TripShield',
        'imcrmLink' => 'https://example.com/q',
    ])->render();

    expect($html)->not->toContain('Sage booking');
});
