<?php

namespace App\Http\Controllers\V2;

use App\Enums\InsuranceProviderEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Http\Controllers\Controller;
use App\Jobs\PolicyIssuanceJob;
use App\Models\InsuranceProvider;
use App\Models\PolicyIssuance;
use App\Models\QuoteType;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;

class PolicyIssuanceController extends Controller
{
    use GenericQueriesAllLobs;

    public function triggerPolicyIssuance($policyIssuanceId, Request $request)
    {
        $policyIssuance = PolicyIssuance::find($policyIssuanceId);
        if (!$policyIssuance) {
            return response()->json(['message' => 'Policy issuance not found'], 404);
        }
        $policyIssuance->status = PolicyIssuanceEnum::PENDING_STATUS;
        if($request->has('completed_step')) {
            $policyIssuance->completed_step = $request->completed_step;
        }
        $policyIssuance->save();
        PolicyIssuanceJob::dispatch($policyIssuance->id)->onQueue('policy-issuance-automation');
        return response()->json(['message' => 'Policy issuance triggered successfully'], 200);
    }

    public function manualTriggerPolicyIssuance(Request $request)
    {
        $insuranceProvider = InsuranceProvider::where('code', InsuranceProviderEnum::AWNI)->first();
        $quoteType = QuoteType::where('id', $request->quote_type_Id)->first();
        $quote = $this->getQuoteObject($quoteType->code, $request->model_id);
        $model = $this->getModelObject($quoteType->code);
        if ($quote->policyIssuance()->exists()) {
            return response()->json(
                [
                    'message' => 'Quote already has a policy issuance, re-trigger the policy process if needs to run again',
                    'policy_issuance_id' => $quote->policyIssuance->id,
                ], 400);
        }
        $policyIssuance = PolicyIssuance::create([
            'insurance_provider_id' => $insuranceProvider->id,
            'model_id' => $request->model_id,
            'model_type' => ltrim($model, '\\'),
            'quote_type' => $quoteType->code,
            'status' => PolicyIssuanceEnum::PENDING_STATUS,
            'completed_step' => null,
        ]);
        PolicyIssuanceJob::dispatch($policyIssuance->id)->onQueue('policy-issuance-automation');
        return response()->json(
            [
                'message' => 'Manual policy issuance triggered successfully',
                'policy_issuance_id' => $policyIssuance->id,
            ], 200);
    }
}
