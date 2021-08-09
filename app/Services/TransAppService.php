<?php

namespace App\Services;
use App\Models\Customer;
use Illuminate\Http\Request;
use Auth;
use DB;
use App\Models\Transaction;
use Illuminate\Support\Facades\Mail;


class TransAppService extends BaseService
{
	public static function createTransaction(Request $request)
    {
        $existingCustomer = CustomerService::getCustomerByEmail($request->email)->first();
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
        if(!$existingCustomer) {
            TransAppService::sendWelcomeEmail($customerId);
        }
        return $approvalCode;
    }

    public static function sendWelcomeEmail($customerId)
    {
        $customer = CustomerService::getCustomerById($customerId);
        $subject = 'Welcome to myAlfred - InsuranceMarket.ae';
        MailService::sendEmail('customerWelcome', [
            'customerName' => $customer->first_name." ".$customer->last_name,
        ], $subject, [$customer->email]);
    }
}
