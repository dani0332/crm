<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\EpCancellationCallbackRequest;
use App\Models\EmbeddedTransaction;
use App\Models\EpLog;
use App\Services\Logger\LoggerService;
use App\Services\SageApiEmbeddedProductService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class EpCancellationCallbackController extends Controller
{
    public function __construct(
        private readonly SageApiEmbeddedProductService $sageApiEmbeddedProductService,
    ) {}

    public function __invoke(EpCancellationCallbackRequest $request): JsonResponse
    {
        $etId = (int) $request->validated('etId');
        $quoteId = (int) $request->validated('quoteId');
        $quoteTypeId = (int) $request->validated('quoteTypeId');

        LoggerService::info('EP cancellation callback received', extra: [
            'etId' => $etId,
            'quoteId' => $quoteId,
            'quoteTypeId' => $quoteTypeId,
        ]);

        $result = $this->sageApiEmbeddedProductService->scheduleReversalOfEmbeddedProduct([
            'etId' => $etId,
            'quoteId' => $quoteId,
            'quoteTypeId' => $quoteTypeId,
        ]);

        $outcome = $this->epCancellationCallbackOutcome($result);

        if ($outcome['logSchedulingWarning']) {
            LoggerService::warning('EP cancellation callback: Sage reversal scheduling failed', extra: [
                'message' => $result['message'],
                'etId' => $etId,
            ]);
        }

        if ($outcome['createScheduledEpLog']) {
            EpLog::create([
                'embedded_transaction_id' => $result['embedded_transaction_id'],
                'event' => 'sage_reversal_scheduled',
                'values' => json_encode([
                    'quote_id' => $quoteId,
                    'quote_type_id' => $quoteTypeId,
                    'message' => $result['message'],
                ]),
                'loggable_id' => $result['embedded_transaction_id'],
                'loggable_type' => (new EmbeddedTransaction)->getMorphClass(),
            ]);
        }

        return response()->json($outcome['payload'], $outcome['statusCode']);
    }

    /**
     * Map service result to HTTP outcome (single exit point from branching).
     *
     * @param  array<string, mixed>  $result
     * @return array{
     *     payload: array{status: bool, message: string},
     *     statusCode: int,
     *     logSchedulingWarning: bool,
     *     createScheduledEpLog: bool
     * }
     */
    private function epCancellationCallbackOutcome(array $result): array
    {
        $message = (string) ($result['message'] ?? '');
        $errorCode = $result['cancellation_callback_error_code'] ?? null;

        return match (true) {
            $errorCode === 'not_found' => [
                'payload' => ['status' => false, 'message' => $message],
                'statusCode' => Response::HTTP_NOT_FOUND,
                'logSchedulingWarning' => false,
                'createScheduledEpLog' => false,
            ],
            $errorCode === 'payment_not_refunded' => [
                'payload' => ['status' => false, 'message' => $message],
                'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'logSchedulingWarning' => false,
                'createScheduledEpLog' => false,
            ],
            ! empty($result['reversal_skipped']) => [
                'payload' => ['status' => true, 'message' => $message],
                'statusCode' => Response::HTTP_OK,
                'logSchedulingWarning' => false,
                'createScheduledEpLog' => false,
            ],
            ! ($result['status'] ?? false) => [
                'payload' => ['status' => false, 'message' => $message],
                'statusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'logSchedulingWarning' => true,
                'createScheduledEpLog' => false,
            ],
            default => [
                'payload' => ['status' => true, 'message' => $message],
                'statusCode' => Response::HTTP_OK,
                'logSchedulingWarning' => false,
                'createScheduledEpLog' => true,
            ],
        };
    }
}
