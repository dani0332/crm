<?php

namespace App\Http\Controllers;

use App\Jobs\InslyDataProcessingJob;
use App\Services\InslyDataService;
use Illuminate\Support\Facades\Log;

class RenewalDataProcessingController extends Controller
{
    public function FetchAndProcessInslyData()
    {
        Log::info('Process FetchAndProcessInslyData trigged');
        $data = InslyDataService::GetDataFromInsly();
        dispatch(new InslyDataProcessingJob($data));
    }
}
