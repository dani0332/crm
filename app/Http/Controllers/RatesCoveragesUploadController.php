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

        return inertia('RatesCoverages/Health/Coverages', [
            'coverages' => $coverages,
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

        return inertia('RatesCoverages/Health/Rates', [
            'rates' => $rates,
        ]);
    }

    public function rateUploadCreate(UploadRateCoverageRequest $request)
    {
        $result = $this->ratesCoveragesUploadFile->rateUploadCreate($request->validated());

        return response()->json(['message' => 'Rates upload is being processed.']);
    }

}
