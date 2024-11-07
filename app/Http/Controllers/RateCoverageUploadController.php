<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadRateCoverageRequest;
use App\Services\RateCoverageUploadService;

class RateCoverageUploadController extends Controller
{
    private $rateCoverageUploadService;

    public function __construct(RateCoverageUploadService $rateCoverageUploadService)
    {
        $this->rateCoverageUploadService = $rateCoverageUploadService;
    }

    /**
     * Fetch upload coverages
     *
     * @return \Inertia\Response
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
     * @param UploadRateCoverageRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function coveragesUploadCreate(UploadRateCoverageRequest $request)
    {
        $this->rateCoverageUploadService->coveragesUploadCreate($request->validated());

        return response()->json(['message' => 'Coverages upload is being processed.']);
    }

    /**
     * Fetch upload rates
     *
     * @return \Inertia\Response
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
     * @param UploadRateCoverageRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function rateUploadCreate(UploadRateCoverageRequest $request)
    {
        $this->rateCoverageUploadService->rateUploadCreate($request->validated());

        return response()->json(['message' => 'Rates upload is being processed.']);
    }

}
