<?php

namespace App\Services;
use App\Models\HealthQuote;
use Illuminate\Http\Request;

class CarQuoteService extends BaseService
{

	public function saveCarQuote(Request $request)
	{
        $healthQuote = new HealthQuote();
        $healthQuote->first_name = $request->first_name;
        $healthQuote->last_name = $request->last_name;
        $healthQuote->email = $request->email;
        $healthQuote->details = $request->details;
        $healthQuote->mobile_no = $request->mobile_no;
        $healthQuote->preference = $request->preference;
        $healthQuote->source = $request->source;
        $healthQuote->marital_status_id = $request->marital_status_id;
        $healthQuote->dob = $request->dob;
        $healthQuote->cover_for_id = $request->cover_for_id;
        $healthQuote->nationality_id = $request->nationality_id;
        $healthQuote->has_dental = $request->has_dental == 'on' ? 1 : 0;
        $healthQuote->has_worldwide_cover = $request->has_worldwide_cover == 'on' ? 1 : 0;
        $healthQuote->has_home = $request->has_home == 'on' ? 1 : 0;
        $healthQuote->emirate_of_your_visa_id = $request->emirate_of_your_visa_id;
        $healthQuote->save();
	}

    public function updateCarQuote(Request $request, $id)
	{
        $healthQuote = HealthQuote::find($id);
        $healthQuote->first_name = $request->first_name;
        $healthQuote->last_name = $request->last_name;
        $healthQuote->email = $request->email;
        $healthQuote->details = $request->details;
        $healthQuote->mobile_no = $request->mobile_no;
        $healthQuote->preference = $request->preference;
        $healthQuote->source = $request->source;
        $healthQuote->marital_status_id = $request->marital_status_id;
        $healthQuote->dob = $request->dob;
        $healthQuote->cover_for_id = $request->cover_for_id;
        $healthQuote->nationality_id = $request->nationality_id;
        $healthQuote->has_dental = $request->has_dental == 'on' ? 1 : 0;
        $healthQuote->has_worldwide_cover = $request->has_worldwide_cover == 'on' ? 1 : 0;
        $healthQuote->has_home = $request->has_home == 'on' ? 1 : 0;
        $healthQuote->emirate_of_your_visa_id = $request->emirate_of_your_visa_id;
        $healthQuote->save();

        if (isset($request->return_to_view))
            return redirect("quote/health/" . $healthQuote->id)->with('success', 'Health Quote has been updated');
	}

    public function fillModelProperties() {
        return array (
            "id" => "readonly|none",
            "first_name" => "input|text|title|required",
            "last_name" => "input|text|required",
            "car_make_id" => "select|title|required",
            "car_model_id" => "select|title|required|dependent|car_make_id"
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
            case 'car_model_id':
                $title = "Car Model";
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
}
