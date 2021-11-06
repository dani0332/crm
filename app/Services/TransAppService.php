<?php

namespace App\Services;
use App\Models\Customer;
use Illuminate\Http\Request;
use Auth;
use DB;
use App\Models\Transaction;
use App\Models\CarQuote;
use App\Models\CarQuotePolicy;
use App\Models\CarQuotePaymentHistory;
use Illuminate\Support\Facades\Mail;
use Config;


class TransAppService extends BaseService
{
	public static function createTransaction(Request $request)
    {
        $existingCustomer = CustomerService::getCustomerByEmail($request->email)->first();
        $sendWelcomeEmail = ($existingCustomer && !$existingCustomer->has_reward_access) || !$existingCustomer ? true : false;
        $customerId = CustomerService::getCustomerIdAndCreateIfNotExists($request->first_name, $request->last_name, $request->email);
        $status_id = DB::table('statuses')->where('name', 'Active')->value('id');
        $transaction = new Transaction;
        $transaction->insurance_company_id = $request->insurance_company;
        $transaction->customer_id = $customerId;
        $transaction->assigned_to_id = $request->assigned_to_id;
        $transaction->created_by_id = Auth::user()->id;
        $transaction->modified_by_id = Auth::user()->id;
        $transaction->payment_mode_id = $request->paymentmode;
        $transaction->risk_details = $request->risk_detail;
        //$transaction->type_of_insurance_id = $request->typeofinsurance;
        $transaction->amount_paid = $request->amount_paid;
        $transaction->status_id = $status_id;
        $transaction->save();
        $approvalCode = generate_code('T').$transaction->id;
        Transaction::where('id',$transaction->id)->update(['approval_code'=>$approvalCode]);
        CustomerService::setCustomerAccess($customerId);

        if($request->has("car_quote_id")) {

            $carQuoteObj = CarQuote::where("id",$request->input("car_quote_id"))->first();
            if($carQuoteObj){
                $carQuoteObj->quote_status_id = 15;// Transaction Approved
                $carQuoteObj->pa_id = null;
                if($carQuoteObj->save()){

                    $newPayment = new CarQuotePaymentHistory();
                    $newPayment->status = "Transaction Approved";
                    $newPayment->notes = "Automate on transaction creations with Transaction ID = ".$transaction->id;
                    $newPayment->car_quote_id = $request->input("car_quote_id");
                    $newPayment->save();

                    $createPolicy = new CarQuotePolicy();
                    $createPolicy->car_quote_id = $request->input("car_quote_id");
                    $createPolicy->transactions_id = $transaction->id;
                    $createPolicy->save();
                }
            }
        }

        if($sendWelcomeEmail && Config::get('constants.ENABLE_TRANSAPP_WE') == '1') {
            TransAppService::sendWelcomeEmail($customerId);
        }
        return $approvalCode;
    }

    public static function sendWelcomeEmail($customerId)
    {
        $customer = CustomerService::getCustomerById($customerId);
        $customer->is_we_sent = true;
        $customer->save();
        $subject = 'Welcome to myAlfred by InsuranceMarket.ae';
        MailService::sendEmail('customerWelcome', [
            'customerName' => $customer->first_name." ".$customer->last_name,
        ], $subject, [$customer->email]);
    }
}
