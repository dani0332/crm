<?php

namespace App\Http\Controllers;

use App\Enums\QuoteTypes;
use App\Jobs\SendFTCEmailJob;
use Illuminate\Http\JsonResponse;

class FtcEmailController extends Controller
{
    /**
     * Dispatch the FTC email job for a given quote type and UUID.
     */
    public function send(string $quoteType, string $uuid): JsonResponse
    {
        $quoteTypeEnum = QuoteTypes::tryFrom(ucfirst($quoteType))
            ?? QuoteTypes::getNameShortCode(strtoupper($quoteType))
            ?? QuoteTypes::getName((int) $quoteType);

        $allowedQuoteTypes = [
            QuoteTypes::CAR,
            QuoteTypes::HOME,
            QuoteTypes::HEALTH,
            QuoteTypes::LIFE,
            QuoteTypes::BUSINESS,
            QuoteTypes::BIKE,
            QuoteTypes::YACHT,
            QuoteTypes::TRAVEL,
            QuoteTypes::PET,
            QuoteTypes::CYCLE,
            QuoteTypes::JETSKI,
            QuoteTypes::SAVINGS,
        ];

        if (! $quoteTypeEnum || ! in_array($quoteTypeEnum, $allowedQuoteTypes, true)) {
            abort(404, 'Quote type not found');
        }

        SendFTCEmailJob::dispatch($uuid, $quoteTypeEnum, false, true);

        return response()->json(['queued' => true, 'time' => now()->format('Y-m-d H:i:s')]);
    }
}
