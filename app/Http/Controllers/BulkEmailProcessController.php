<?php
namespace App\Http\Controllers;

use App\Jobs\MailServiceJob;
use App\Models\Customer;
use Illuminate\Http\Request;

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
        $from = date($request->dateFrom);
        $to = date($request->dateTo);
        $customers = Customer::select('*')
        ->whereBetween('created_at', [$from, $to])->get();
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
            //break;
        }
    }
}
