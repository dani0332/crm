<?php

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\UploadRateCoverageRequest;
use App\Services\RateCoverageUploadService;
use Illuminate\Http\JsonResponse;
use Inertia\Response;

class RateCoverageUploadController extends Controller
{
    private $rateCoverageUploadService;

    public function __construct(RateCoverageUploadService $rateCoverageUploadService)
    {
        $this->middleware(['permission:'.PermissionsEnum::UPLOAD_HEALTH_RATES], ['only' => ['uploadRates', 'rateUploadCreate']]);
        $this->middleware(['permission:'.PermissionsEnum::UPLOAD_HEALTH_COVERAGES], ['only' => ['uploadCoverages', 'coveragesUploadCreate']]);
        $this->middleware(['permission:'.PermissionsEnum::UPLOAD_HEALTH_COVERAGES.'|'.PermissionsEnum::UPLOAD_HEALTH_RATES], ['only' => ['badRecords']]);
        $this->rateCoverageUploadService = $rateCoverageUploadService;
    }

    /**
     * Fetch upload coverages
     *
     * @return Response
     */
    public function uploadCoverages()
    {
        $coverages = $this->rateCoverageUploadService->getUploadCoverages();
        $azureStorageUrl = config('constants.AZURE_IM_STORAGE_URL');
        $azureStorageContainer = config('constants.AZURE_IM_STORAGE_CONTAINER');

        return inertia('RatesCoverages/Health/Coverages', [
            'coverages' => $coverages,
            'azureStorageUrl' => $azureStorageUrl,
            'azureStorageContainer' => $azureStorageContainer,
        ]);
    }

    /**
     * Upload coverages file.
     *
     * @return JsonResponse
     */
    public function coveragesUploadCreate(UploadRateCoverageRequest $request)
    {
        // Not Contains private data so no need to check for private storage
        $this->rateCoverageUploadService->coveragesUploadCreate($request->validated());

        return response()->json(['message' => 'Coverages upload is being processed.']);
    }

    /**
     * Fetch upload rates
     *
     * @return Response
     */
    public function uploadRates()
    {
        $rates = $this->rateCoverageUploadService->getUploadRates();
        $azureStorageUrl = config('constants.AZURE_IM_STORAGE_URL');
        $azureStorageContainer = config('constants.AZURE_IM_STORAGE_CONTAINER');

        return inertia('RatesCoverages/Health/Rates', [
            'rates' => $rates,
            'azureStorageUrl' => $azureStorageUrl,
            'azureStorageContainer' => $azureStorageContainer,
        ]);
    }

    /**
     * Upload rates.
     *
     * @return JsonResponse
     */
    public function rateUploadCreate(UploadRateCoverageRequest $request)
    {
        $this->rateCoverageUploadService->rateUploadCreate($request->validated());

        return response()->json(['message' => 'Rates upload is being processed.']);
    }

    /**
     * Fetch bad records.
     *
     * @param  int  $id
     * @return Response
     */
    public function badRecords($id)
    {
        $badRecords = $this->rateCoverageUploadService->getBadRecords($id);

        return response()->json(['status' => true, 'data' => $badRecords]);
    }

}
