<?php
namespace App\Http\Controllers;

use App\Jobs\RewardsBulkWEJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BulkEmailProcessController extends Controller
{
    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function __construct()
    {

    }

    public function ProcessBulkWelcomeEmails(Request $request)
    {
        Log::channel('daily')->info('Process Bulk Welcome Email Method trigged');
        dispatch(new RewardsBulkWEJob(json_encode($request), $request->dateTo, $request->dateFrom));
        return "Success";
    }
}
