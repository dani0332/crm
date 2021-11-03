<?php

namespace App\Services;
use App\Models\HealthQuote;
use Illuminate\Http\Request;
use DB;
class HealthQuoteService extends BaseService
{
    protected $query;
    protected $crudService;
    public function __construct(CRUDService $crudService)
    {
        $this->crudService = $crudService;
        $this->query = "
                    SELECT hqr.id
                    ,hqr.first_name
                    ,hqr.last_name
                    ,hqr.email
                    ,hqr.mobile_no
                    ,hqr.preference
                    ,hqr.details
                    ,hqr.source
                    ,hqr.dob
                    ,hqr.has_dental
                    ,hqr.has_home
                    ,hqr.has_worldwide_cover
                    ,hqr.marital_status_id
                    ,ms.TEXT AS marital_status_id_text
                    ,hqr.cover_for_id
                    ,hcf.TEXT AS cover_for_id_text
                    ,hqr.nationality_id
                    ,n.TEXT AS nationality_id_text
                    ,hqr.emirate_of_your_visa_id
                    ,e.TEXT AS emirate_of_your_visa_id_text
                FROM health_quote_request hqr
                INNER JOIN marital_status ms ON ms.id = hqr.marital_status_id
                INNER JOIN health_cover_for hcf ON hcf.id = hqr.cover_for_id
                INNER JOIN nationality n ON n.id = hqr.nationality_id
                INNER JOIN emirates e ON e.id = hqr.emirate_of_your_visa_id";
    }

    public function getEntity($id){
        return DB::select($this->query.' where hqr.id = '. $id);
    }

	public function saveHealthQuote(Request $request)
	{
        $dataArr = array(
            "firstName" => $request->first_name,
            "lastName" => $request->last_name,
            "email" => $request->email,
            "details" => $request->details,
            "mobileNo" => $request->mobile_no,
            "preference" => $request->preference,
            "source" => $request->source,
            "maritalStatusId" => $request->marital_status_id,
            "dob" => $request->dob,
            "coverForId" => $request->cover_for_id,
            "nationalityId" => $request->nationality_id,
            "hasDental" => $request->has_dental == 'on' ? true : false,
            "hasWorldwideCover" => $request->has_worldwide_cover == 'on' ?  true : false,
            "hasHome" => $request->has_home == 'on' ? true : false,
            "emirateOfYourVisaId" => $request->emirate_of_your_visa_id,
        );
        #return $this->crudService->sendCAPIRequest('/api/v1-save-health-quote', $dataArr);
	}

    public function getGridData($searchProperties, $request){
        $count = 0;
        if ($request->ajax()) {
            foreach ($searchProperties as $item) {
                if(!empty($request[$item])){
                    $suffix = '';
                    switch ($item) {
                        case 'marital_status_id':
                            $suffix = 'ms';
                            break;
                        case 'health_cover_for':
                            $suffix = 'hcf';
                            break;
                        case 'nationality':
                            $suffix = 'n';
                            break;
                        case 'emirates':
                            $suffix = 'e';
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

    public function updateHealthQuote(Request $request, $id)
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
        $healthQuote->has_dental = $request->has_dental == 'on' ? true : false;
        $healthQuote->has_worldwide_cover = $request->has_worldwide_cover == 'on' ? true : false;
        $healthQuote->has_home = $request->has_home == 'on' ? true : false;
        $healthQuote->emirate_of_your_visa_id = $request->emirate_of_your_visa_id;
        $healthQuote->save();

        if (isset($request->return_to_view))
            return redirect("quote/health/" . $healthQuote->id)->with('success', 'Health Quote has been updated');
	}

    public function fillModelProperties() {
        return array (
            "id" => "readonly|none",
            "first_name" => "input|text|required",
            "last_name" => "input|text|required",
            "preference" => "input|text",
            "email" => "input|email|required",
            "details" => "input|text",
            "mobile_no" => "input|title|number|required",
            "source" => "input|text|title",
            "dob" => 'input|date|required',
            "marital_status_id" => "select|title|required",
            "cover_for_id" => "select|title|required",
            "nationality_id" => "select|title|required",
            "has_dental" => "input|checkbox|title",
            "has_worldwide_cover" => "input|checkbox|title",
            "has_home" => "input|checkbox|title",
            "emirate_of_your_visa_id" => "select|title|required"
        );
    }

    public function getCustomTitleByProperty($propertyName){
        $title = "";
        switch ($propertyName) {
            case 'marital_status_id':
                $title = "Marital Status";
                break;
            case 'cover_for_id':
                $title = "Who would you like cover for?";
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
            "list" => "email,cover_for_id,has_worldwide_cover,has_home,details,preference",
        ];
    }

    public function fillModelSearchProperties(){
        return ["email", 'first_name', 'last_name', 'nationality_id'];
    }
}
