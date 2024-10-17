<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadRateCoverageRequest;
use App\Jobs\UploadCoveragesJob;
use App\Jobs\UploadRatesJob;

class RatesCoveragesUploadController extends Controller
{
    public function uploadCoverages()
    {
        return inertia('RatesCoverages/Health/Coverages');
    }

    public function coveragesUploadCreate(UploadRateCoverageRequest $request)
    {
        $file = $request->file('file_name')->store('uploads');
        UploadCoveragesJob::dispatch($file);

        return response()->json(['message' => 'Coverages upload is being processed.']);
    }

    public function uploadRates()
    {
        return inertia('RatesCoverages/Health/Rates');
    }

    public function rateUploadCreate(UploadRateCoverageRequest $request)
    {
        $file = $request->file('file_name')->store('uploads');
        UploadRatesJob::dispatch($file);

        return response()->json(['message' => 'Rates upload is being processed.']);
    }

}
