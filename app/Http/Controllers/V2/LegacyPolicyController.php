<?php

namespace App\Http\Controllers\V2;

use App\Enums\LegacyPolicyEnum;
use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Repositories\InslyDetailRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LegacyPolicyController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:'.PermissionsEnum::VIEW_LEGACY_DETAILS, ['only' => ['index', 'show', 'moveToImcrm']]);
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
}
