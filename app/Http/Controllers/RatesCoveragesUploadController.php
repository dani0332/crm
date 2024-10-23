<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadRateCoverageRequest;
use App\Jobs\UploadRatesJob;
use App\Models\RateCoveragesProcess;
use App\Models\RatesCoveragesUpload;
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
        $coverages = RateCoveragesProcess::select(
            'rate_coverage_uploads.file_name as fileName',
            'rate_coverage_uploads.total_records as totalRecords',
            'rate_coverage_uploads.good as good',
            'rate_coverage_uploads.cannot_upload as cannotUpload',
            'rate_coverage_uploads.id as upload_id',
            'rate_coverage_processes.type as type',
            'rate_coverage_processes.validation_errors as error'
        )
            ->leftJoin('rate_coverage_uploads', 'rate_coverage_processes.rate_coverage_id', '=', 'rate_coverage_uploads.id')
            ->simplePaginate()->withQueryString();

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
        return inertia('RatesCoverages/Health/Rates');
    }

    public function rateUploadCreate(UploadRateCoverageRequest $request)
    {
        $file = $request->file('file_name')->store('uploads');
        UploadRatesJob::dispatch($file);

        return response()->json(['message' => 'Rates upload is being processed.']);
    }

}
