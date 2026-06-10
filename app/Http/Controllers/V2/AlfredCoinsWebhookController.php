<?php

declare(strict_types=1);

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Services\AlfredCoinsWebhookService;
use App\Services\Logger\LoggerService;
use Symfony\Component\HttpFoundation\Response;

class AlfredCoinsWebhookController extends Controller
{
    public function __construct(private AlfredCoinsWebhookService $alfredCoinsWebhookService) {}

    public function reTrigger($list)
    {
        $quoteList = explode(',', $list);

        if (count($quoteList) === 0) {
            return response()->json([
                'message' => 'No quote UIDs provided.',
            ], Response::HTTP_BAD_REQUEST);
        } elseif (count($quoteList) > 10) {
            return response()->json([
                'message' => 'Greater than 10 not allowed.',
            ], Response::HTTP_BAD_REQUEST);
        }

        foreach ($quoteList as $quote) {
            $quote = explode('-', $quote);
            $quoteTypeId = (int) $quote[0];
            $quoteUuid = $quote[1];

            LoggerService::info('AlfredCoinsWebhookController - Re-triggering Alfred Coins webhook via route', [], [
                'quoteUuid' => $quoteUuid,
                'quoteTypeId' => $quoteTypeId,
            ]);
            $this->alfredCoinsWebhookService->sendInsuranceMarketWebhook(
                $quoteUuid,
                $quoteTypeId
            );
        }

        return response()->json([
            'message' => count($quoteList).' Alfred Coins webhook(s) triggered.',
        ], Response::HTTP_OK);
    }
}
