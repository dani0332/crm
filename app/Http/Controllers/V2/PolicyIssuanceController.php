<?php

namespace App\Http\Controllers\V2;

use App\Enums\PolicyIssuanceEnum;
use App\Http\Controllers\Controller;
use App\Jobs\PolicyIssuanceJob;
use App\Models\PolicyIssuance;
use Illuminate\Http\Request;

class PolicyIssuanceController extends Controller
{
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
}
