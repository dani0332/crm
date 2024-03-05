<?php

namespace App\Http\Controllers;

use App\Jobs\InslyDataProcessingJob;
use App\Services\InslyDataService;
use Illuminate\Support\Facades\Log;

//Scheduled to delete 1st April 2024
class RenewalDataProcessingController extends Controller
{
    public function FetchAndProcessInslyData()
    {
        Log::info('Process FetchAndProcessInslyData trigged');
        $data = InslyDataService::GetDataFromInsly();
        dispatch(new InslyDataProcessingJob($data));
    }
}
