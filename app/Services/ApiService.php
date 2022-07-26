<?php

namespace App\Services;
use App\Services\CustomerWEGenerateUrlService;
use App\Models\MyAlFredUser;
use App\Services\CustomerService;
use App\Services\CreateUpdateContactService;
use App\Models\Customer;
use App\Models\HealthQuote;
use Exception, Log, Config;

class ApiService
{
    public function fetchSignupUrl($request){
        try {
            return $this->checkmyAlredLink($request->email, $request);
        } catch(Exception $e) {
            Log::error($e->getLine() ." ".$e->getMessage() ." ".$e->getFile());
            return response()->json(["message" => "Something went wrong. Please try again later."], 500);
        }
    }

    private function checkmyAlredLink($email, $request) {
        $customer = CustomerService::getUniqueCustomerByEmail($email);
        if($customer) {
            $data = MyAlFredUser::select('signup_url')->whereCustomerId($customer->id)->latest()->first();
            if($data) {
                return response()->json(["message" => $data->signup_url], 200);
            } else {
                if(!$customer->is_we_sent)
                    return $this->generateSignupUrl($customer, $request);
                else
                    return response()->json(["message" => "Signup url does not exists against the Customer"], 404);
            }
        }
        return response()->json(["message" => "Customer does not exists"], 404);
    }

    private function generateSignupUrl($customer) {
        $WEGenerateUrlResponse = CustomerWEGenerateUrlService::getCustomerWeUrl();
        if(gettype($WEGenerateUrlResponse) == 'string') {
            Customer::where("id", $customer->id)->update(['is_we_sent' => true]);

            $newMyAlFredUser = new MyAlFredUser();
            $newMyAlFredUser->signup_url = $WEGenerateUrlResponse;
            $newMyAlFredUser->customer_id = $customer->id;
            $newMyAlFredUser->code =  substr($WEGenerateUrlResponse, strpos($WEGenerateUrlResponse, "signup/") + 7); // code;
            $newMyAlFredUser->source = "IMCRM";
            $newMyAlFredUser->save();
            return response()->json(["message" => $newMyAlFredUser->signup_url], 500);
        }else
            return response()->json(["message" => $WEGenerateUrlResponse], 500);

    }

    public function triggerSibFlow($request){
        try {
            $quoteData = HealthQuote::where('uuid', $request->quoteUID)->where('quote_status_id', $request->QuoteStatus)->first();
            if($quoteData) {
                $data = [
                    'customerName' => $quoteData->full_name,
                    'advisorName' => $quoteData->advisor->name,
                    'advisorEmail' => $quoteData->advisor->email,
                    'advisorMobile' => $quoteData->advisor->mobile_no,
                    'customerLastName' => $quoteData->last_name,
                    'customerFirstName' => $quoteData->first_name,
                    'lead_status' => $quoteData->quoteStatus->text,
                    'cbdid' => $quoteData->code,
                    'link' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$quoteData->uuid
                ];
                return CreateUpdateContactService::contactCreateUpdate(config('constants.SIB_HEALTH_EBP_LIST_ID'), $quoteData->first_name, $quoteData->last_name, $quoteData->email, false, $data);
            }
            
        } catch(Exception $e) {
            Log::error($e->getLine() ." ".$e->getMessage() ." ".$e->getFile());
            return response()->json(["message" => "Something went wrong. Please try again later."], 500);
        }
    }
}
