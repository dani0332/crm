<?php

namespace App\Services;

use App\Enums\QuoteStatusEnum;
use App\Models\Customer;
use App\Models\HealthQuote;
use App\Models\MyAlFredUser;
use App\Traits\CreateUpdateSIbContact;
use Exception;
use Log;

class ApiService
{
    use CreateUpdateSIbContact;

    public function fetchSignupUrl($request)
    {
        try {
            return $this->checkmyAlredLink($request->email, $request);
        } catch (Exception $e) {
            Log::error($e->getLine().' '.$e->getMessage().' '.$e->getFile());

            return response()->json(['message' => 'Something went wrong. Please try again later.'], 500);
        }
    }

    private function checkmyAlredLink($email, $request)
    {
        $customer = CustomerService::getUniqueCustomerByEmail($email);
        if ($customer) {
            $data = MyAlFredUser::select('signup_url')->whereCustomerId($customer->id)->latest()->first();
            if ($data) {
                return response()->json(['message' => $data->signup_url], 200);
            } else {
                if (! $customer->is_we_sent) {
                    return $this->generateSignupUrl($customer, $request);
                } else {
                    return response()->json(['message' => 'Signup url does not exists against the Customer'], 404);
                }
            }
        }

        return response()->json(['message' => 'Customer does not exists'], 404);
    }

    private function generateSignupUrl($customer)
    {
        $WEGenerateUrlResponse = CustomerWEGenerateUrlService::getCustomerWeUrl();
        if (gettype($WEGenerateUrlResponse) == 'string') {
            Customer::where('id', $customer->id)->update(['is_we_sent' => true]);

            $newMyAlFredUser = new MyAlFredUser();
            $newMyAlFredUser->signup_url = $WEGenerateUrlResponse;
            $newMyAlFredUser->customer_id = $customer->id;
            $newMyAlFredUser->code = substr($WEGenerateUrlResponse, strpos($WEGenerateUrlResponse, 'signup/') + 7); // code;
            $newMyAlFredUser->source = 'IMCRM';
            $newMyAlFredUser->save();

            return response()->json(['message' => $newMyAlFredUser->signup_url], 500);
        } else {
            return response()->json(['message' => $WEGenerateUrlResponse], 500);
        }
    }

    public function triggerSibFlow($request)
    {
        try {
            $quoteData = HealthQuote::where('uuid', $request->quoteUID)->where('quote_status_id', $request->QuoteStatus)->first();
            if ($quoteData) {
                $this->sendSibRequest($quoteData);
            }
        } catch (Exception $e) {
            return response()->json(['message' => 'Something went wrong. Please try again later.'], 500);
        }
    }

    public function sibHealthQuoteCallBack($code)
    {
        HealthQuote::where('code', $code)->update(['quote_status_id' => QuoteStatusEnum::InNegotiation]);
    }
}
