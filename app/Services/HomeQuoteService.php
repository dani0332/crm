<?php

namespace App\Services;
use App\Models\HomeQuote;
use Illuminate\Http\Request;
use DB;

class HomeQuoteService extends BaseService
{
    protected $query;

    public function __construct()
    {
        $this->query = "
                SELECT hqr.id
                ,hqr.first_name
                ,hqr.last_name
                ,hqr.email
                ,hqr.mobile_no
                ,hqr.address
                ,hqr.has_contents
                ,hqr.contents_aed
                ,hqr.has_personal_belongings
                ,hqr.personal_belongings_aed
                ,hqr.has_building
                ,hqr.building_aed
                ,hqr.ilivein_accommodation_type_id
                ,hat.TEXT AS ilivein_accommodation_type_id_text
                ,hqr.iam_possesion_type_id
                ,hpt.TEXT AS iam_possesion_type_id_text
            FROM central_afia.home_quote_request hqr
            INNER JOIN home_accommodation_type hat ON hat.id = hqr.ilivein_accommodation_type_id
            INNER JOIN home_possession_type hpt ON hpt.id = hqr.iam_possesion_type_id";
    }

    public function getEntity($id){
        return DB::select($this->query.' where hqr.id = '. $id);
    }

	public function saveHomeQuote(Request $request)
	{
        $homeQuote = new HomeQuote();
        $homeQuote->first_name = $request->first_name;
        $homeQuote->last_name = $request->last_name;
        $homeQuote->email = $request->email;
        $homeQuote->details = $request->details;
        $homeQuote->mobile_no = $request->mobile_no;
        $homeQuote->preference = $request->preference;
        $homeQuote->source = $request->source;
        $homeQuote->marital_status_id = $request->marital_status_id;
        $homeQuote->dob = $request->dob;
        $homeQuote->cover_for_id = $request->cover_for_id;
        $homeQuote->nationality_id = $request->nationality_id;
        $homeQuote->has_dental = $request->has_dental == 'on' ? 1 : 0;
        $homeQuote->has_worldwide_cover = $request->has_worldwide_cover == 'on' ? 1 : 0;
        $homeQuote->has_home = $request->has_home == 'on' ? 1 : 0;
        $homeQuote->emirate_of_your_visa_id = $request->emirate_of_your_visa_id;
        $homeQuote->save();
	}

    public function getGridData($searchProperties, $request){
        $count = 0;
        if ($request->ajax()) {
            foreach ($searchProperties as $item) {
                if(!empty($request[$item])){
                    $suffix = '';
                    switch ($item) {
                        case 'ilivein_accommodation_type':
                            $suffix = 'hat';
                            break;
                        case 'iam_possesion_type':
                            $suffix = 'hpt';
                            break;
                        default:
                            $suffix = 'hqr';
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

    public function updateHomeQuote(Request $request, $id)
	{
        $homeQuote = HomeQuote::find($id);
        $homeQuote->first_name = $request->first_name;
        $homeQuote->last_name = $request->last_name;
        $homeQuote->email = $request->email;
        $homeQuote->details = $request->details;
        $homeQuote->mobile_no = $request->mobile_no;
        $homeQuote->preference = $request->preference;
        $homeQuote->source = $request->source;
        $homeQuote->marital_status_id = $request->marital_status_id;
        $homeQuote->dob = $request->dob;
        $homeQuote->cover_for_id = $request->cover_for_id;
        $homeQuote->nationality_id = $request->nationality_id;
        $homeQuote->has_dental = $request->has_dental == 'on' ? 1 : 0;
        $homeQuote->has_worldwide_cover = $request->has_worldwide_cover == 'on' ? 1 : 0;
        $homeQuote->has_home = $request->has_home == 'on' ? 1 : 0;
        $homeQuote->emirate_of_your_visa_id = $request->emirate_of_your_visa_id;
        $homeQuote->save();

        if (isset($request->return_to_view))
            return redirect("quote/home/" . $homeQuote->id)->with('success', 'Home Quote has been updated');
	}

    public function fillModelProperties() {
        return array (
            "id" => "readonly|none",
            "first_name" => "input|text|required",
            "last_name" => "input|text|required",
            "email" => "input|email|required",
            "mobile_no" => "input|title|number|required",
            "contents_aed" => "input|number|required",
            "personal_belongings_aed" => "input|number|required",
            "building_aed" => "input|number|required",
            "iam_possesion_type_id" => "select|title|required",
            "ilivein_accommodation_type_id" => "select|title|required",
            "has_contents" => "input|checkbox|required",
            "has_personal_belongings" => "input|checkbox|required",
            "has_building" => "input|checkbox|required",
            "address" => 'textarea|required',
        );
    }

    public function getCustomTitleByProperty($propertyName){
        $title = "";
        switch ($propertyName) {
            case 'iam_possesion_type_id':
                $title = "I am";
                break;
            case 'ilivein_accommodation_type_id':
                $title = "I Live In";
                break;
            case 'mobile_no':
                $title = "Mobile Number";
                break;
            default:
                break;
        }
        return $title;
    }

    public function fillModelSkipProperties() {
        return [
            "create" => "id",
            "list" => "email,address,iam_possesion_type_id,ilivein_accommodation_type_id",
        ];
    }

    public function fillModelSearchProperties(){
        return ["email", 'first_name', 'last_name', 'iam_possesion_type_id', 'ilivein_accommodation_type_id'];
    }
}
