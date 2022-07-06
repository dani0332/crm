<?php
namespace App\Http\Controllers;
use App\Services\InslyDataService;
use App\Jobs\InslyDataProcessingJob;
use Illuminate\Support\Facades\Log;

class RenewalDataProcessingController extends Controller
{

    public function FetchAndProcessInslyData()
    {
        Log::channel('daily')->info('Process FetchAndProcessInslyData trigged');
        $data = InslyDataService::GetDataFromInsly();
        dispatch(new InslyDataProcessingJob($data));
    }
}
