<?php

namespace App\Http\Controllers\V2;

use App\Enums\AwnicEnum;
use App\Enums\InsuranceProviderEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReTriggerPolicyAutomationRequest;
use App\Jobs\PolicyIssuanceJob;
use App\Models\InsuranceProvider;
use App\Models\PolicyIssuance;
use App\Models\QuoteType;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class PolicyIssuanceController extends Controller
{
    use GenericQueriesAllLobs;

    public function __construct(private PolicyIssuanceService $policyIssuanceService) {}

    public function triggerPolicyIssuance($policyIssuanceId, Request $request)
    {
        $policyIssuance = PolicyIssuance::find($policyIssuanceId);
        if (! $policyIssuance) {
            return response()->json(['message' => 'Policy issuance not found'], Response::HTTP_NOT_FOUND);
        }
        if ($policyIssuance->status == PolicyIssuanceEnum::PROCESSING_STATUS || $policyIssuance->status == PolicyIssuanceEnum::BOOKING_PROCESSING_STATUS) {
            return response()->json(['message' => 'Policy issuance still in processing state cannot start another'], Response::HTTP_BAD_REQUEST);
        }
        $policyIssuance->status = PolicyIssuanceEnum::PENDING_STATUS;
        if ($request->has('completed_step') && ! in_array($request->completed_step, [AwnicEnum::STEP_BOOK_POLICY])) {
            $policyIssuance->completed_step = $request->completed_step;
        }
        $policyIssuance->save();
        PolicyIssuanceJob::dispatch($policyIssuance->id);

        return response()->json(['message' => 'Policy issuance triggered successfully'], Response::HTTP_OK);
    }

    public function manualTriggerPolicyIssuance(Request $request)
    {
        $insuranceProvider = InsuranceProvider::where('code', InsuranceProviderEnum::AWNI->value)->first();
        $quoteType = QuoteType::where('id', $request->quote_type_Id)->first();
        $quote = $this->getQuoteObject($quoteType->code, $request->model_id);
        $model = $this->getModelObject($quoteType->code);
        if ($quote && $quote->policyIssuance()->exists()) {
            return response()->json(
                [
                    'message' => 'Quote already has a policy issuance, re-trigger the policy process if needs to run again',
                    'policy_issuance_id' => $quote->policyIssuance->id,
                ], Response::HTTP_BAD_REQUEST);
        }
        $policyIssuance = PolicyIssuance::create([
            'insurance_provider_id' => $insuranceProvider->id,
            'model_id' => $request->model_id,
            'model_type' => ltrim($model, '\\'),
            'quote_type' => $quoteType->code,
            'status' => PolicyIssuanceEnum::PENDING_STATUS,
            'completed_step' => null,
        ]);
        PolicyIssuanceJob::dispatch($policyIssuance->id);

        return response()->json(
            [
                'message' => 'Manual policy issuance triggered successfully',
                'policy_issuance_id' => $policyIssuance->id,
            ], Response::HTTP_OK);
    }

    public function reTriggerPolicyAutomation(ReTriggerPolicyAutomationRequest $request): JsonResponse
    {
        $policyIssuance = PolicyIssuance::query()
            ->with(['insuranceProvider', 'model'])
            ->find($request->integer('policy_issuance_id'));

        if (! $policyIssuance) {
            return response()->json(['message' => 'Policy issuance not found'], Response::HTTP_NOT_FOUND);
        }

        if (! $this->policyIssuanceService->shouldOfferReTriggerPolicyAutomation($policyIssuance)) {
            return response()->json(['message' => 'Policy issuance is not eligible for re-trigger.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $policyIssuance->refresh();

            $this->policyIssuanceService->reTriggerPolicyAutomation($policyIssuance);
        } catch (ModelNotFoundException $exception) {
            return response()->json(['message' => 'Policy issuance not found'], Response::HTTP_NOT_FOUND);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json(['message' => 'Policy automation re-triggered successfully.'], Response::HTTP_OK);
    }
}
