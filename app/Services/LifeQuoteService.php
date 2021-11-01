<?php

namespace App\Services;
use App\Models\LifeQuote;
use Illuminate\Http\Request;
use DB;

class LifeQuoteService extends BaseService
{
    protected $query;

    public function __construct()
    {
        $this->query = "
                        SELECT lqr.id
                        ,lqr.first_name
                        ,lqr.last_name
                        ,lqr.email
                        ,lqr.mobile_no
                        ,lqr.gender
                        ,lqr.dob
                        ,lqr.is_smoker
                        ,lqr.others_info
                        ,lqr.sum_insured_value
                        ,lqr.sum_insured_currency_id
                        ,ct.TEXT AS sum_insured_currency_id_text
                        ,lqr.marital_status_id
                        ,ms.TEXT AS marital_status_id_text
                        ,lqr.purpose_of_insurance_id
                        ,lip.TEXT AS purpose_of_insurance_id_text
                        ,lqr.children_id
                        ,lc.TEXT AS children_id_text
                        ,lqr.tenure_of_insurance_id
                        ,lit.TEXT AS tenure_of_insurance_id_text
                        ,lqr.number_of_years_id
                        ,liy.TEXT AS number_of_years_id_text
                        ,lqr.nationality_id
                        ,n.TEXT AS nationality_id_text
                    FROM central_afia.life_quote_request lqr
                    INNER JOIN currency_type ct ON ct.id = lqr.sum_insured_currency_id
                    INNER JOIN marital_status ms ON ms.id = lqr.marital_status_id
                    INNER JOIN life_insurance_purpose lip ON lip.id = lqr.purpose_of_insurance_id
                    INNER JOIN life_children lc ON lc.id = lqr.children_id
                    INNER JOIN life_insurance_tenure lit ON lit.id = lqr.tenure_of_insurance_id
                    INNER JOIN life_number_of_year liy ON liy.id = lqr.number_of_years_id
                    INNER JOIN nationality n ON n.id = lqr.nationality_id";
    }
	public function saveLifeQuote(Request $request)
	{
        $lifeQuote = new LifeQuote();
        $lifeQuote->first_name = $request->first_name;
        $lifeQuote->last_name = $request->last_name;
        $lifeQuote->email = $request->email;
        $lifeQuote->details = $request->details;
        $lifeQuote->mobile_no = $request->mobile_no;
        $lifeQuote->travel_cover_for_id = $request->travel_cover_for_id;
        $lifeQuote->nationality_id = $request->nationality_id;
        $lifeQuote->days_cover_for = $request->days_cover_for;
        $lifeQuote->destination = $request->destination;
        $lifeQuote->region_cover_for_id = $request->region_cover_for_id;
        $lifeQuote->details = $request->details;
        $lifeQuote->save();
	}

    public function getEntity($id){
        return DB::select($this->query.' where lqr.id = '. $id);
    }

    public function getGridData($searchProperties, $request){
        $count = 0;
        if ($request->ajax()) {
            foreach ($searchProperties as $item) {
                if(!empty($request[$item])){
                    $suffix = '';
                    switch ($item) {
                        case 'sum_insured_currency_id':
                            $suffix = 'ct';
                            break;
                        case 'marital_status_id':
                            $suffix = 'ms';
                            break;
                        case 'nationality_id':
                            $suffix = 'n';
                            break;
                        case 'purpose_of_insurance_id':
                            $suffix = 'lip';
                            break;
                        case 'children_id':
                            $suffix = 'lc';
                            break;
                        case 'tenure_of_insurance_id':
                            $suffix = 'lit';
                            break;
                        case 'number_of_years_id':
                            $suffix = 'liy';
                            break;
                        default:
                            $suffix = 'lqr';
                            break;
                    }
                    if($count == 0){
                        $this->query = $this->query.' where '.$suffix.'.'.$item.'='."'".$request[$item]."'";
                    }else{
                        $this->query = $this->query.' and '.$suffix.'.'.$item.'='."'".$request[$item]."'";
                    }
                    $count++;
                }
            }
        }

        return DB::select($this->query);
    }

    public function updateLifeQuote(Request $request, $id)
	{
        $lifeQuote = LifeQuote::find($id);
        $lifeQuote->first_name = $request->first_name;
        $lifeQuote->last_name = $request->last_name;
        $lifeQuote->email = $request->email;
        $lifeQuote->details = $request->details;
        $lifeQuote->mobile_no = $request->mobile_no;
        $lifeQuote->dob = $request->dob;
        $lifeQuote->gender = $request->gender == 'Male' ? 'M' : 'F';
        $lifeQuote->is_smoker = $request->is_smoker == 'Yes' ? '1' : '0';
        $lifeQuote->save();

        if (isset($request->return_to_view))
            return redirect("quote/life/" . $lifeQuote->id)->with('success', 'Life Quote has been updated');
	}

    public function fillModelProperties() {
        return array (
            "id" => "readonly|none",
            "first_name" => "input|text|required",
            "last_name" => "input|text|required",
            "mobile_no" => "input|text|title|required",
            "email" => "input|email|required",
            "mobile_no" => "input|number|title||required",
            "dob" => "input|date|title|required",
            "sum_insured_value" => "input|number|title||required",
            "sum_insured_currency_id" => "select|title|required",
            "purpose_of_insurance_id" => "select|title|required",
            "children_id" => "select|title|required",
            "tenure_of_insurance_id" => "select|title|required",
            "number_of_years_id" => "select|title|required",
            "gender" => "static|required|Male,Female",
            "is_smoker" => "static|title|required|Yes,No",
            "others_info" => "textarea",
        );
    }

    public function getCustomTitleByProperty($propertyName){
        $title = "";
        switch ($propertyName) {
            case 'purpose_of_insurance_id':
                $title = "Purpose of Insurance";
                break;
            case 'children_id':
                $title = "Children";
                break;
            case 'tenure_of_insurance_id':
                $title = "Tenure Of Insurance";
                break;
            case 'number_of_years_id':
                $title = "No. of Years";
                break;
            case 'sum_insured_currency_id':
                $title = "Currency";
                break;
            case 'dob':
                $title = "Date Of Birth";
                break;
            case 'mobile_no':
                $title = "Mobile Number";
                break;
            case 'is_smoker':
                $title = "Smoker";
                break;
            case 'mobile_no':
                $title = "Mobile Number";
                break;
            case 'sum_insured_value':
                $title = "Sum Insured Value";
                break;
            default:
                break;
        }
        return $title;
    }

    public function fillModelSkipProperties() {
        return [
            "create" => "id",
            "list" => "email,mobile_no",
        ];
    }

    public function fillModelSearchProperties(){
        return ["email", 'first_name', 'last_name', 'nationality_id', 'region_cover_for_id', 'travel_cover_for_id'];
    }
}
