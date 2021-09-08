<?php
namespace App\Http\Controllers;

use App\Jobs\MailServiceJob;
use App\Models\Customer;
use Illuminate\Http\Request;
use Config;

use function PHPUnit\Framework\isEmpty;

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
        $bulkEmailBatchLimit = Config::get('constants.BULK_WE_EMAIL_BATCH_LIMIT');
        $from = date($request->dateFrom);
        $to = date($request->dateTo);
        do{
            $customers = Customer::whereBetween('created_at', [$from, $to])
            ->where('has_reward_access', 1)
            ->where('is_we_sent', 0)
            ->skip(0)->take($bulkEmailBatchLimit)
            ->get();
            foreach ($customers as $customer) {
                $request->to = $customer->email;
                $params = ["customerName" => $customer->first_name.' '.$customer->last_name];
                $request->subject = 'Welcome to myAlfred by InsuranceMarket.ae';
                $request->templateName = 'customerWelcome';
                $request->templateParams = $params;
                if(!$customer->is_we_sent){
                    dispatch(new MailServiceJob(json_encode($request)));
                    $customer->is_we_sent = true;
                    $customer->save();
                }
            }
        } while(!isEmpty($customers));
    }
}
