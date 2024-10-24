<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadRateCoverageRequest;
use App\Services\RatesCoveragesUploadService;

class RatesCoveragesUploadController extends Controller
{
    private $ratesCoveragesUploadFile;

    public function __construct(RatesCoveragesUploadService $ratesCoveragesUploadFile)
    {
        $this->ratesCoveragesUploadFile = $ratesCoveragesUploadFile;
    }
    public function uploadCoverages()
    {
        $coverages = $this->ratesCoveragesUploadFile->getUploadCoverages();
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

        $result = $this->ratesCoveragesUploadFile->coveragesUploadCreate($request->validated());

        return response()->json(['message' => 'Coverages upload is being processed.']);
    }

    public function uploadRates()
    {
        $rates = $this->ratesCoveragesUploadFile->getUploadRates();
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
        $result = $this->ratesCoveragesUploadFile->rateUploadCreate($request->validated());

        return response()->json(['message' => 'Rates upload is being processed.']);
    }

}
