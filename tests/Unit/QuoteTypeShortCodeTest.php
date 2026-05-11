<?php

use App\Enums\QuoteTypeShortCode;

it('maps quote type ids to short codes and back', function (int $id, string $shortCode) {
    expect(QuoteTypeShortCode::getName($id))->toBe($shortCode)
        ->and(QuoteTypeShortCode::getId($shortCode))->toBe($id);
})->with([
    [1, QuoteTypeShortCode::CAR],
    [2, QuoteTypeShortCode::HOM],
    [3, QuoteTypeShortCode::HEA],
    [4, QuoteTypeShortCode::LIF],
    [5, QuoteTypeShortCode::BUS],
    [6, QuoteTypeShortCode::BIK],
    [7, QuoteTypeShortCode::YAC],
    [8, QuoteTypeShortCode::TRA],
    [9, QuoteTypeShortCode::PET],
    [10, QuoteTypeShortCode::CYC],
    [11, QuoteTypeShortCode::JSK],
    [18, QuoteTypeShortCode::SAV],
    [20, QuoteTypeShortCode::DEV],
]);
