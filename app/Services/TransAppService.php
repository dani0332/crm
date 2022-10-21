<?php

namespace App\Services;

use App\Models\CarQuote;
use App\Models\CarQuotePaymentHistory;
use App\Models\CarQuotePolicy;
use App\Models\InsuranceCompany;
use App\Models\MyAlFredUser;
use App\Models\PaymentMode;
use App\Models\Reason;
use App\Models\Status;
use App\Models\Transaction;
use App\Models\TypeOfInsurance;
use App\Models\User;
use Auth;
use Carbon\Carbon;
use Config;
use DB;
use Illuminate\Http\Request;
use LookUpModel;

class TransAppService extends BaseService
{
    protected $sendEmailCustomerService;
    protected $sendSmsCustomerService;
    protected $applicationStorageService;

    public function __construct(
        SendEmailCustomerService $sendEmailCustomerService,
        SendSmsCustomerService $sendSmsCustomerService,
        ApplicationStorageService $applicationStorageService,
    ) {
        $this->sendEmailCustomerService = $sendEmailCustomerService;
        $this->sendSmsCustomerService = $sendSmsCustomerService;
        $this->applicationStorageService = $applicationStorageService;
    }

    public function createTransaction(Request $request)
    {
        $WEGenerateUrlResponse = CustomerWEGenerateUrlService::getCustomerWeUrl();

        if (gettype($WEGenerateUrlResponse) == 'string') {
            $existingCustomer = CustomerService::getCustomerByEmail($request->email)->first();
            $sendWelcomeEmail = ($existingCustomer && ! $existingCustomer->is_we_sent) || ! $existingCustomer ? true : false;
            $customerId = CustomerService::getCustomerIdAndCreateIfNotExists($request->first_name, $request->last_name, $request->email);
            $statusId = DB::table('statuses')->where('name', 'Active')->value('id');

            if ($existingCustomer != null) { // Existing customer
                if ($existingCustomer->is_we_sent == 1) { // is_we_sent is true
                    $responseExtend = CustomerExtendSubscriptionService::extendCustomerSubscription($customerId);

                    $listId = Config::get('constants.SIB_MYALFRED_CONTACTS_LIST_ID');
                    $responseContact = CreateUpdateContactService::contactCreateUpdate($listId, $request->first_name, $request->last_name, $request->email, $WEGenerateUrlResponse);

                    if ($responseContact != 201 && $responseContact != 204) {
                        $message = 'myAlfred signup link to issued policy cases (SIB API)<br>
                        Customer Email: '.$request->email;
                        info($message);
                    }

                    if ($responseExtend != 201) {
                        $customerToken = MyAlFredUser::select('code')->where('customer_id', '=', $customerId)->orderBy('created_at', 'asc')->first();
                        $message = 'Customer trying to extend subscription but not exist in myAflred<br>
                        Customer Email: '.$request->email.'<br>
                        Token: '.$customerToken;
                        info($message);
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
            Transaction::where('id', $transaction->id)->update(['approval_code' => $approvalCode]);
            CustomerService::setCustomerAccess($customerId);

            if ($request->has('car_quote_id')) {
                $carQuoteObj = CarQuote::where('id', $request->input('car_quote_id'))->first();
                if ($carQuoteObj) {
                    $carQuoteObj->quote_status_id = LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'transaction_approved']); // Transaction Approved
                    $carQuoteObj->pa_id = null;
                    if ($carQuoteObj->save()) {
                        $newPayment = new CarQuotePaymentHistory();
                        $newPayment->status = 'Transaction Approved';
                        $newPayment->notes = $approvalCode;
                        $newPayment->car_quote_id = $request->input('car_quote_id');
                        $newPayment->save();

                        $createPolicy = new CarQuotePolicy();
                        $createPolicy->car_quote_id = $request->input('car_quote_id');
                        $createPolicy->transactions_id = $transaction->id;
                        $createPolicy->save();
                    }
                }
            }

            $expiryDate = Carbon::now()->addMonths(12);
            $customer = CustomerService::getCustomerById($customerId);
            $customerEmail = $customer->email;
            $customerMobile = $customer->mobile_no;
            $customer->myalfred_expiry_date = $expiryDate;
            $customer->save();

            $isCustomerExisting = MyAlFredUser::where('customer_id', '=', $customerId)->get();

            $isSmsTestingEnabled = $this->applicationStorageService->getValueByKey('IS_MA_SMS_AFIA_TESTING_ENABLE');

            if ($customerMobile != null) {
                if ($isSmsTestingEnabled == 0) {
                    $this->sendWelcomeSms($customerMobile, $WEGenerateUrlResponse, $customerEmail);
                } else {
                    $isAfiaTester = $this->isAfiaEmail($customerEmail);
                    if ($isAfiaTester) {
                        $this->sendWelcomeSms($customerMobile, $WEGenerateUrlResponse, $customerEmail);
                    }
                }
            }

            if ($sendWelcomeEmail && Config::get('constants.ENABLE_TRANSAPP_WE') == '1' && $isCustomerExisting->isEmpty()) {
                $this->sendWelcomeEmail($customerId, $WEGenerateUrlResponse, 'transapp-myalfred-we');
            }

            return $approvalCode;
        } else {
            return $WEGenerateUrlResponse;
        }
    }

    public function sendWelcomeEmail($customerId, $WEGenerateUrlResponse, $tag)
    {
        $customer = CustomerService::getCustomerById($customerId);
        $emailTemplateId = (int) Config::get('constants.SIB_MYALFRED_CUSTOMER_WE_TEMPLATE_ID'); //290

        $emailData = (object) [
            'customerName' => $customer->first_name.' '.$customer->last_name,
            'customerEmail' => $customer->email,
            'signUpButtonUrl' => $WEGenerateUrlResponse,
        ];

        $getStatusCode = $this->sendEmailCustomerService->sendEmail($emailTemplateId, $emailData, $tag);

        if ($getStatusCode == 201) {
            $customer->is_we_sent = true;
            $customer->save();

            $code = substr($WEGenerateUrlResponse, strpos($WEGenerateUrlResponse, 'signup/') + 7);
            $newMyAlFredUser = new MyAlFredUser;
            $newMyAlFredUser->signup_url = $WEGenerateUrlResponse;
            $newMyAlFredUser->customer_id = $customerId;
            $newMyAlFredUser->code = $code;
            $newMyAlFredUser->source = 'TRANSAPP';
            $newMyAlFredUser->save();
        }
    }

    public function sendWelcomeSms($customerMobile, $WEGenerateUrlResponse, $customerEmail)
    {
        if (preg_match('/^(?:971|\+971|0)?(?:50|51|52|55|56|58|2|3|4|6|7|9)\d{7}$/', $customerMobile)) {
            $mobileNumber = preg_replace('/^(?:971|\+971|0)/', '971', $customerMobile);
        } else {
            $mobileNumber = null;
        }

        if ($mobileNumber != null) {
            $shortUrl = $this->sendSmsCustomerService->getShortUrl($WEGenerateUrlResponse);
            $smsMessage = 'As a valued customer of InsuranceMarket.ae, you can avail offers from over 100 brands on myAlfred. Click '.$shortUrl.' to enjoy the offers! optoutMA 4741';
            $this->sendSmsCustomerService->sendSms($mobileNumber, $smsMessage);
        } else {
            info('Invalid mobile number: '.$customerMobile.' | email: '.$customerEmail.' | class: '.get_class());
        }
    }

    public function getTransactors()
    {
        $transactors = User::select('users.id', 'users.name')
            ->leftjoin('model_has_roles', 'users.id', 'model_has_roles.model_id')
            ->leftjoin('roles', 'roles.id', 'model_has_roles.role_id')
            ->whereIn('roles.name', ['TRANSAPP_ADVISOR', 'TRANSAPP_APPROVER', 'TRANSAPP_ADMIN'])
            ->orderBy('users.name', 'asc')
            ->get();

        return $transactors;
    }

    public function getHandlers()
    {
        $handlers = User::select('users.id', 'users.name')
            ->leftjoin('model_has_roles', 'users.id', 'model_has_roles.model_id')
            ->leftjoin('roles', 'roles.id', 'model_has_roles.role_id')
            ->whereIn('roles.name', ['TRANSAPP_ADVISOR', 'TRANSAPP_APPROVER', 'TRANSAPP_ADMIN', 'advisor', 'invoicing'])
            ->orderBy('users.name', 'asc')
            ->get();

        return $handlers;
    }

    public function getInsuranceCompanies()
    {
        $insuranceCompanies = InsuranceCompany::select('id', 'name')
            ->where(['is_active' => 1, 'is_deleted' => 0])
            ->orderBy('name', 'asc')
            ->get();

        return $insuranceCompanies;
    }

    public function getPaymentModes()
    {
        $paymentModes = PaymentMode::select('id', 'name')
            ->where(['is_active' => 1, 'is_deleted' => 0])
            ->orderBy('name', 'asc')
            ->get();

        return $paymentModes;
    }

    public function getReasons()
    {
        $reasons = Reason::select('id', 'name')
            ->where(['is_active' => 1, 'is_deleted' => 0])
            ->orderBy('name', 'asc')
            ->get();

        return $reasons;
    }

    public function checkTransappAdmin()
    {
        if (Auth::user()->hasRole('TRANSAPP_ADMIN')) {
            $isTransappAdmin = '1';
        } else {
            $isTransappAdmin = '0';
        }

        return $isTransappAdmin;
    }

    public function checkTransappNonAdmin()
    {
        if (Auth::user()->hasAnyRole(['TRANSAPP_ADVISOR', 'TRANSAPP_APPROVER'])) {
            $isTransappNonAdmin = '1';
        } else {
            $isTransappNonAdmin = '0';
        }

        return $isTransappNonAdmin;
    }

    public function getTypeOfInsurances()
    {
        $typeofinsurances = TypeOfInsurance::select('id', 'text')
            ->where(['is_active' => 1, 'is_deleted' => 0])
            ->orderBy('text', 'asc')
            ->get();

        return $typeofinsurances;
    }

    public function getTransactionDetailById($transappId)
    {
        $isTransappNonAdmin = $this->checkTransappNonAdmin();

        $transaction = Transaction::select(
            'transactions.*',
            'insurance_companies.name as insurance',
            'handlers.name as handler_name',
            'creaters.name as created_by_name',
            'payment_modes.name as payment_mode',
            'type_of_insurances.text as type_of_insurance'
        )
            ->leftjoin('insurance_companies', 'insurance_companies.id', 'transactions.insurance_company_id')
            ->leftjoin('users as handlers', 'transactions.assigned_to_id', 'handlers.id')
            ->leftjoin('users as creaters', 'transactions.created_by_id', 'creaters.id')
            ->leftjoin('payment_modes', 'payment_modes.id', 'transactions.payment_mode_id')
            ->leftjoin('type_of_insurances', 'type_of_insurances.id', 'transactions.type_of_insurance_id')
            ->where(['transactions.id' => $transappId, 'transactions.is_deleted' => 0])
            ->first();

        if ($isTransappNonAdmin == '1') {
            $transaction->where('transactions.assigned_to_id', Auth::user()->id);
        }

        return $transaction;
    }

    public function getTransactionDetailByApprovalCode($approvalCode)
    {
        $query = Transaction::select(
            'transactions.*',
            'insurance_companies.name as insurance',
            'handlers.name as handler_name',
            'creaters.name as created_by_name',
            'payment_modes.name as payment_mode'
        )
            ->leftjoin('insurance_companies', 'insurance_companies.id', 'transactions.insurance_company_id')
            ->leftjoin('users as handlers', 'transactions.assigned_to_id', 'handlers.id')
            ->leftjoin('users as creaters', 'transactions.created_by_id', 'creaters.id')
            ->leftjoin('payment_modes', 'payment_modes.id', 'transactions.payment_mode_id')
            ->where(['approval_code' => $approvalCode, 'transactions.is_deleted' => 0])
            ->where('approval_code', $approvalCode);
        if ($this->checkTransappNonAdmin() == '1') {
            $query->where('transactions.assigned_to_id', auth()->id());
        }

        return $query->first();
    }

    public function getTransappAssignedToIdByApprovalCode($approvalCode)
    {
        $transappAssignedToId = Transaction::where('approval_code', $approvalCode)
            ->pluck('assigned_to_id')->first();

        return $transappAssignedToId;
    }

    public function getTransappIsCancelledByApprovalCode($approvalCode)
    {
        $isCancelled = Transaction::where('approval_code', $approvalCode)
            ->pluck('is_cancelled')->first();

        return $isCancelled;
    }

    public function getTransactionByApprovalCode($approvalCode)
    {
        $transaction = Transaction::where('approval_code', $approvalCode)
            ->get();

        return $transaction;
    }

    public function getPreviousTransactionByApprovalCode($approvalCode)
    {
        $transaction = Transaction::where('approval_code', $approvalCode)
            ->first();

        return $transaction;
    }

    public function getStatuses()
    {
        $statuses = Status::select('id', 'name')
            ->where(['is_active' => 1, 'is_deleted' => 0])
            ->orderBy('name', 'asc')
            ->get();

        return $statuses;
    }

    public function getStatusId($status)
    {
        $statusId = Status::select('id')
            ->where('name', $status)
            ->first();

        return $statusId->id;
    }

    public function getTransappApprovalCodeById($transappId)
    {
        $approvalCode = Transaction::select('approval_code')
            ->where('id', $transappId)
            ->first();

        return $approvalCode->approval_code;
    }

    public function isAfiaEmail($email)
    {
        $isAfiaEmail = false;

        $acceptedDomains = ['afia.ae', 'insurancemarket.ae'];

        if (in_array(substr($email, strrpos($email, '@') + 1), $acceptedDomains)) {
            $isAfiaEmail = true;
        }

        return $isAfiaEmail;
    }
}
