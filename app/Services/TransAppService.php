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
use Config;
use LookUpModel;

class TransAppService extends BaseService
{
	public static function createTransaction(Request $request)
    {
        $WEGenerateUrlResponse = CustomerWEGenerateUrlService::getCustomerWeUrl();

        if(gettype($WEGenerateUrlResponse) == 'string') {

            $existingCustomer = CustomerService::getCustomerByEmail($request->email)->first();
            $sendWelcomeEmail = ($existingCustomer && !$existingCustomer->is_we_sent) || !$existingCustomer ? true : false;
            $customerId = CustomerService::getCustomerIdAndCreateIfNotExists($request->first_name, $request->last_name, $request->email);
            $statusId = DB::table('statuses')->where('name', 'Active')->value('id');

            if($existingCustomer != null) { // Existing customer
                if($existingCustomer->is_we_sent == 1) { // is_we_sent is true

                    $response = CustomerExtendSubscriptionService::extendCustomerSubscription($customerId);

                    $listId = Config::get('constants.SIB_MYALFRED_CONTACTS_LIST_ID');
                    CreateUpdateContactService::contactCreateUpdate($listId, $request->first_name, $request->last_name, $request->email);

                    if($response == 422) {
                        $customerToken = MyAlFredUser::select('code')->where('customer_id', '=', $customerId)->orderBy('created_at','asc')->first();
                        $message = "Customer trying to extend subscription but not exist in myAflred<br>
                        Customer Email: ".$request->email."<br>
                        Token: ".$customerToken;
                    }
                }
            }

            $transaction = new Transaction;
            $transaction->insurance_company_id = $request->insurance_company;
            $transaction->customer_id = $customerId;
            $transaction->assigned_to_id = $request->assigned_to_id;
            $transaction->created_by_id = Auth::user()->id;
            $transaction->modified_by_id = Auth::user()->id;
            $transaction->payment_mode_id = $request->paymentmode;
            $transaction->risk_details = $request->risk_detail;
            $transaction->amount_paid = $request->amount_paid;
            $transaction->status_id = $statusId;
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
                TransAppService::sendWelcomeEmail($customerId, $WEGenerateUrlResponse, $tag='transapp-myalfred-we');
            }
            return $approvalCode;
        }
        else {
            return $WEGenerateUrlResponse;
        }

    }

    public static function sendWelcomeEmail($customerId, $WEGenerateUrlResponse, $tag)
    {
        $customer = CustomerService::getCustomerById($customerId);
        $emailTemplateId = (int)Config::get('constants.SIB_MYALFRED_CUSTOMER_WE_TEMPLATE_ID'); //290

        $emailData = array(
            'customerName' => $customer->first_name." ".$customer->last_name,
            'customerEmail' => $customer->email,
            'signUpButtonUrl' => $WEGenerateUrlResponse
        );

        $getStatusCode = SendEmailCustomerService::sendEmail($emailTemplateId, $emailData, $tag);

        if($getStatusCode == 201) {

            $customer->is_we_sent = true;
            $customer->save();

            $code = substr($WEGenerateUrlResponse, strpos($WEGenerateUrlResponse, "signup/") + 7);
            $newMyAlFredUser = new MyAlFredUser;
            $newMyAlFredUser->signup_url = $WEGenerateUrlResponse;
            $newMyAlFredUser->customer_id = $customerId;
            $newMyAlFredUser->code = $code;
            $newMyAlFredUser->source = "TRANSAPP";
            $newMyAlFredUser->save();
        }
    }
}
