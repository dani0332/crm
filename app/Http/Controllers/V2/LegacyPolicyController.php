<?php

namespace App\Http\Controllers\V2;

use App\Enums\LegacyPolicyEnum;
use App\Enums\PermissionsEnum;
use App\Facades\Capi;
use App\Http\Controllers\Controller;
use App\Repositories\InslyDetailRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LegacyPolicyController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:'.PermissionsEnum::VIEW_LEGACY_DETAILS.'|'.PermissionsEnum::VIEW_ALL_LEADS, ['only' => ['index', 'show', 'moveToImcrm']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $policies = [];
        if ($request->hasAny(['policy_number', 'email', 'mobile_no'])) {
            $policies = InslyDetailRepository::getData();
        }
        $legacyPolicyMapping = LegacyPolicyEnum::INSLY_PRODUCT_MAPPING;
        $coveragePolicyMapping = LegacyPolicyEnum::INSLY_COVERAGE_MAPPING;

        return inertia('LegacyPolicy/Index', ['policies' => $policies, 'legacyPolicyMapping' => $legacyPolicyMapping, 'coveragePolicyMapping' => $coveragePolicyMapping]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($mongoId)
    {
        $policy = InslyDetailRepository::getBy('_id', $mongoId);

        return inertia('LegacyPolicy/Show', ['policy' => $policy]);
    }

    public function moveToImcrm(Request $request)
    {

        $policy = InslyDetailRepository::saveToImcrm($request->toArray());

        return $policy;
    }

    public function getS3TempUrl(Request $request)
    {
        $expiryDate = now()->addMinutes(40);
        $fileName = $request->fileName;
        $temporaryUrl = null;
        if (Storage::disk('insly_documents')->has($fileName)) {
            $temporaryUrl = Storage::disk('insly_documents')->temporaryUrl($fileName, $expiryDate);
        }
        // Check if a temporary URL was generated
        if ($temporaryUrl) {
            return response()->json(['url' => $temporaryUrl]);
        } else {
            return response()->json(['error' => 'File does not exists on server']);
        }
    }

    public function getPolicyByPolicyNumber($policyNumber)
    {
        $policy = InslyDetailRepository::getBy('policy_no', $policyNumber);

        return redirect()->route('legacy-policy.show', ['legacy_policy' => $policy->_id]);
    }

    /**
     * This function initiates the migration of a policy identified by the given policy ID.
     * It sends a POST request to the '/api/migrate-policy' endpoint with the policy ID.
     *
     * @param  int  $policyId  The ID of the policy to be migrated.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function migratePolicy(Request $request, $policyId)
    {
        info('Function: migratePolicy initiated for policy ID: '.$policyId);

        // Prepare the data array with 'policyIds' key containing the single policy ID inside an array
        $requestData = [
            'policyIds' => [
                $policyId,
            ],
        ];

        try {
            // Send the POST request with the requestData array
            $response = Capi::request('/api/migrate-policy', 'post', $requestData);

            // Ensure the response has the result array and it's not empty
            if (! isset($response->result) || ! is_array($response->result) || empty($response->result)) {
                throw new \Exception('Unexpected response structure from API.');
            }

            // Extract the response message
            $responseMessage = $response->result[0]['msg'] ?? 'Failed!';

            // Check the response message and redirect accordingly
            if ($responseMessage == 'Failed!') {
                return redirect()->back()->with('error', 'Migration failed for Policy ID: '.$policyId);
            }

            return redirect()->back()->with('success', $responseMessage);
        } catch (\Exception $e) {
            // Log the exception and redirect with an error message
            Log::error('Policy migration failed for policy ID: '.$policyId.'. Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred during policy migration. Please try again later.');
        }
    }
}
