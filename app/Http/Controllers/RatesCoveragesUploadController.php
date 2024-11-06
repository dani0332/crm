<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadRateCoverageRequest;
use App\Services\RatesCoveragesUploadService;

class RatesCoveragesUploadController extends Controller
{
    private $ratesCoveragesUploadService;

    public function __construct(RatesCoveragesUploadService $ratesCoveragesUploadService)
    {
        $this->ratesCoveragesUploadService = $ratesCoveragesUploadService;
    }
    public function uploadCoverages()
    {
        $coverages = $this->ratesCoveragesUploadService->getUploadCoverages();
        $azureStorageUrl = config('constants.AZURE_IM_STORAGE_URL');
        $azureStorageContainer = config('constants.AZURE_IM_STORAGE_CONTAINER');

        return inertia('RatesCoverages/Health/Coverages', [
            'coverages' => $coverages,
            'azureStorageUrl' => $azureStorageUrl,
            'azureStorageContainer' => $azureStorageContainer,
        ]);
    }

    public function coveragesUploadCreate(UploadRateCoverageRequest $request)
    {
        $this->ratesCoveragesUploadService->coveragesUploadCreate($request->validated());

        return response()->json(['message' => 'Coverages upload is being processed.']);
    }

    public function uploadRates()
    {
        $rates = $this->ratesCoveragesUploadService->getUploadRates();
        $azureStorageUrl = config('constants.AZURE_IM_STORAGE_URL');
        $azureStorageContainer = config('constants.AZURE_IM_STORAGE_CONTAINER');

        return inertia('RatesCoverages/Health/Rates', [
            'rates' => $rates,
            'azureStorageUrl' => $azureStorageUrl,
            'azureStorageContainer' => $azureStorageContainer,
        ]);
    }

    public function rateUploadCreate(UploadRateCoverageRequest $request)
    {
        $this->ratesCoveragesUploadService->rateUploadCreate($request->validated());

        return response()->json(['message' => 'Rates upload is being processed.']);
    }

}
