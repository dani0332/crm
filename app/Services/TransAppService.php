<?php

namespace App\Services;
use Illuminate\Http\Request;
use Auth;
use DB;
use App\Models\Transaction;
use App\Models\CarQuote;
use App\Models\CarQuotePolicy;
use App\Models\CarQuotePaymentHistory;
use App\Models\MyAlFredUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Config;
use LookUpModel;

class TransAppService extends BaseService
{
	public static function createTransaction(Request $request)
    {
        $WEGenerateUrlResponse = CustomerWEGenerateUrlService::getCustomerWeUrl($request);

        if(gettype($WEGenerateUrlResponse) == 'string') {

            $existingCustomer = CustomerService::getCustomerByEmail($request->email)->first();
            $sendWelcomeEmail = ($existingCustomer && !$existingCustomer->is_we_sent) || !$existingCustomer ? true : false;
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
            $transaction->amount_paid = $request->amount_paid;
            $transaction->status_id = $status_id;
            $transaction->save();
            $approvalCode = generate_code('T').$transaction->id;
            Transaction::where('id',$transaction->id)->update(['approval_code'=>$approvalCode]);
            CustomerService::setCustomerAccess($customerId);

            if($request->has("car_quote_id")) {

                $carQuoteObj = CarQuote::where("id",$request->input("car_quote_id"))->first();
                if($carQuoteObj){
                    $carQuoteObj->quote_status_id =  LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'transaction_approved']);// Transaction Approved
                    $carQuoteObj->pa_id = null;
                    if($carQuoteObj->save()){

                        $newPayment = new CarQuotePaymentHistory();
                        $newPayment->status = "Transaction Approved";
                        $newPayment->notes = $approvalCode;
                        $newPayment->car_quote_id = $request->input("car_quote_id");
                        $newPayment->save();

                        $createPolicy = new CarQuotePolicy();
                        $createPolicy->car_quote_id = $request->input("car_quote_id");
                        $createPolicy->transactions_id = $transaction->id;
                        $createPolicy->save();
                    }
                }
            }

            $expiryDate = Carbon::now()->addMonths(12);
            $customer = CustomerService::getCustomerById($customerId);
            $customer->myalfred_expiry_date = $expiryDate;
            $customer->save();


            if($sendWelcomeEmail && Config::get('constants.ENABLE_TRANSAPP_WE') == '1') {
                TransAppService::sendWelcomeEmail($customerId, $WEGenerateUrlResponse);
            }
            return $approvalCode;
        }
        else {
            return $WEGenerateUrlResponse;
        }

    }

    public static function sendWelcomeEmail($customerId, $WEGenerateUrlResponse)
    {
        $customer = CustomerService::getCustomerById($customerId);
        $customer->is_we_sent = true;
        $customer->save();

        $appEnv = Config::get('constants.APP_ENV');
        $emailSubject = 'Act now and simply sign up to cash in your rewards & keep on saving!';
        $emailRecipient = $customer->email;
        $fromName = 'Alfred';
        $replyToEmail = Config::get('constants.MAIL_MYALFRED_SUPPORT_REPLY_TO');
        $emailSubjectExt = $appEnv." | ".$emailSubject;

        if($appEnv == "production") {
            $emailSubject = $emailSubject;
            $fromEmail = 'no-reply@alert.insurancemarket.email';
        }
        else {
            $emailSubject = $emailSubjectExt;
            $fromEmail = 'no-reply@alert.instacover.ae';
        }

        Mail::send(['html' => 'customerWelcome'], [
            'customerName' => $customer->first_name." ".$customer->last_name,
            'customerEmail' => $customer->email,
            'signUpButtonUrl' => $WEGenerateUrlResponse,
        ],
            function ($message) use ($emailSubject, $emailRecipient, $fromName, $fromEmail, $replyToEmail) {
                $message->to($emailRecipient)->replyTo($replyToEmail)->subject($emailSubject);
                $message->from($fromEmail, $fromName);
        });

        $code = substr($WEGenerateUrlResponse, strpos($WEGenerateUrlResponse, "signup/") + 7); // code

        $newMyAlFredUser = new MyAlFredUser;
        $newMyAlFredUser->signup_url = $WEGenerateUrlResponse;
        $newMyAlFredUser->customer_id = $customerId;
        $newMyAlFredUser->code = $code;
        $newMyAlFredUser->source = "TRANSAPP";
        $newMyAlFredUser->save();
    }
}
