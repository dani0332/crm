<?php

namespace App\Services;
use App\Models\CarQuote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class CarQuoteService extends BaseService
{
	public function saveCarQuote(Request $request)
	{
        $carQuote = new CarQuote();
        $carQuote->first_name = $request->first_name;
        $carQuote->last_name = $request->last_name;
        $carQuote->email = $request->email;
        $carQuote->details = $request->details;
        $carQuote->mobile_no = $request->mobile_no;
        $carQuote->preference = $request->preference;
        $carQuote->source = $request->source;
        $carQuote->marital_status_id = $request->marital_status_id;
        $carQuote->dob = $request->dob;
        $carQuote->cover_for_id = $request->cover_for_id;
        $carQuote->nationality_id = $request->nationality_id;
        $carQuote->has_dental = $request->has_dental == 'on' ? 1 : 0;
        $carQuote->has_worldwide_cover = $request->has_worldwide_cover == 'on' ? 1 : 0;
        $carQuote->has_home = $request->has_home == 'on' ? 1 : 0;
        $carQuote->emirate_of_your_visa_id = $request->emirate_of_your_visa_id;
        $carQuote->save();
	}

    public function updateCarQuote(Request $request, $id)
	{
        $carQuote = CarQuote::find($id);
        $carQuote->first_name = $request->first_name;
        $carQuote->last_name = $request->last_name;
        $carQuote->email = $request->email;
        $carQuote->details = $request->details;
        $carQuote->mobile_no = $request->mobile_no;
        $carQuote->preference = $request->preference;
        $carQuote->source = $request->source;
        $carQuote->marital_status_id = $request->marital_status_id;
        $carQuote->dob = $request->dob;
        $carQuote->cover_for_id = $request->cover_for_id;
        $carQuote->nationality_id = $request->nationality_id;
        $carQuote->has_dental = $request->has_dental == 'on' ? 1 : 0;
        $carQuote->has_worldwide_cover = $request->has_worldwide_cover == 'on' ? 1 : 0;
        $carQuote->has_home = $request->has_home == 'on' ? 1 : 0;
        $carQuote->emirate_of_your_visa_id = $request->emirate_of_your_visa_id;
        $carQuote->save();

        if (isset($request->return_to_view))
            return redirect("quote/health/" . $carQuote->id)->with('success', 'Health Quote has been updated');
	}

    public function fillModelProperties() {
        return array (
            "id" => "readonly|none",
            "first_name" => "input|text|title|required",
            "last_name" => "input|text|required",
            "dob" => "input|date|required",
            "car_make_id" => "select|title|required",
            "additional_notes" => "textarea|required",
        );
    }

    public function getCustomTitleByProperty($propertyName){
        $title = "";
        switch ($propertyName) {
            case 'first_name':
                $title = "My Name";
                break;
            case 'car_make_id':
                $title = "Car Make";
                break;
            case 'nationality_id':
                $title = "Nationality";
                break;
            case 'mobile_no':
                $title = "Mobile Number";
                break;
            case 'has_dental':
                $title = "Dental";
                break;
            case 'has_worldwide_cover':
                $title = "WorldWide Cover";
                break;
            case 'has_home':
                $title = "Home Country Cover";
                break;
            case 'source':
                $title = "Lead Source";
                break;
            case 'emirate_of_your_visa_id':
                $title = "Emirate of your visa";
                break;
            case 'dob':
                $title = "Date of Birth";
                break;
            default:
                break;
        }
        return $title;
    }

    public function fillModelSkipProperties() {
        return [
            "create" => "id",
            "list" => "",
        ];
    }

    public function fillModelSearchProperties(){
        return [];
    }

    public function getQuotePlans($id) {

        $quoteUuId = CarQuote::where('id', '=', $id)->value('uuid');

        $plansApiEndPoint = Config::get('constants.KEN_PLANS_API_ENDPOINT');
        $plansApiToken = Config::get('constants.KEN_PLANS_API_TOKEN');
        $plansApiTimeout = Config::get('constants.KEN_PLANS_API_TIMEOUT');

        $plansDataArr = array(
            "quoteUID" => $quoteUuId,
            "lang" => "en",
        );

        $client = new \GuzzleHttp\Client();
        $kenRequest = $client->post(
            $plansApiEndPoint,
            [
                'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'x-api-token' => $plansApiToken],
                'body' => json_encode($plansDataArr),
                'timeout' => $plansApiTimeout,
            ]
        );

        $getStatusCode = $kenRequest->getStatusCode();

        if($getStatusCode == 200) {
            $getContents = $kenRequest->getBody();
            $getdecodeContents = json_decode($getContents);
            return $getdecodeContents;
        }
        else {
            return "API failed";
        }
    }
}
