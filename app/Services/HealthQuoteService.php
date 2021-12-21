<?php

namespace App\Services;

use App\Models\HealthQuote;
use Illuminate\Http\Request;
use DB;
use Auth;

class HealthQuoteService extends BaseService
{
    protected $query;
    public function __construct()
    {
        $this->query = DB::table('health_quote_request as hqr')->
        select('hqr.id','hqr.uuid','hqr.code','hqr.first_name','hqr.updated_at','hqr.created_at','hqr.last_name','hqr.email','hqr.mobile_no','hqr.preference','hqr.details','hqr.source','hqr.dob'
        ,'hqr.has_dental','hqr.has_home','hqr.has_worldwide_cover','hqr.marital_status_id','ms.TEXT AS marital_status_id_text','hqr.cover_for_id'
        ,'hcf.TEXT AS cover_for_id_text','hqr.nationality_id','n.TEXT AS nationality_id_text','hqr.emirate_of_your_visa_id'
        ,'hqr.quote_status_id', 'qs.text as quote_status_id_text'
        ,'e.TEXT AS emirate_of_your_visa_id_text','hqr.advisor_id','u.name as advisor_id_text')
        ->leftJoin('marital_status as ms', 'ms.id', '=', 'hqr.marital_status_id')
        ->leftJoin('health_cover_for as hcf', 'hcf.id', '=', 'hqr.cover_for_id')
        ->leftJoin('nationality as n', 'n.id', '=', 'hqr.nationality_id')
        ->leftJoin('emirates as e', 'e.id', '=', 'hqr.emirate_of_your_visa_id')
        ->leftJoin('quote_status as qs', 'qs.id', '=', 'hqr.quote_status_id')
        ->leftJoin('users as u', 'u.id', '=', 'hqr.advisor_id');
    }

    public function getEntity($id)
    {
        return $this->query->where('hqr.uuid', $id)->first();
    }

    public function getEntityPlain($id)
    {
        return HealthQuote::where('id', $id)->first();
    }
    public function getLeadsForAssignment()
    {
        return HealthQuote::orderBy('created_at', 'desc')->get();
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
        if(Auth::user()->hasRole("HEALTH_ADVISOR")) $dataArr['advisorId'] = Auth::users()->id;
        return CapiRequestService::sendCAPIRequest('/api/v1-save-health-quote', $dataArr);
    }

    public function getGridData($searchProperties, $request)
    {
        if ($request->ajax()) {
            foreach ($searchProperties as $item) {
                if (!empty($request[$item])) {
                    $this->query->where($this->getQuerySuffix($item) . '.' . $item, $request[$item]);
                }
            }
        }
        $this->query->orderBy('hqr.created_at', 'DESC');
        return $this->query;
    }

    private function getQuerySuffix($item)
    {
        switch ($item) {
            case 'marital_status_id':
                return 'ms';
                break;
            case 'health_cover_for':
                return 'hcf';
                break;
            case 'nationality':
                return 'n';
                break;
            case 'advisor':
                return 'u';
                break;
            case 'quote_status':
                return 'qs';
                break;
            case 'emirates':
                return 'e';
                break;
            case 'advisor':
                return 'u';
                break;
            default:
            return 'hqr';
                break;
        }
    }

    public function updateHealthQuote(Request $request, $id)
    {
        $updateArray = [
            'first_name'=>$request->first_name,
            'last_name'=>$request->last_name,
            'details'=>$request->details,
            'preference' => $request->preference,
            'source' => $request->source,
            'marital_status_id' => $request->marital_status_id,
            'dob' => $request->dob,
            'cover_for_id' => $request->cover_for_id,
            'nationality_id' => $request->nationality_id,
            'has_dental' => $request->has_dental == 'on' ? true : false,
            'has_worldwide_cover' => $request->has_worldwide_cover == 'on' ? true : false,
            'has_home' => $request->has_home == 'on' ? true : false,
            'emirate_of_your_visa_id' => $request->emirate_of_your_visa_id,
        ];
        if(!Auth::user()->hasRole('HOME_ADVISOR')){
            $updateArray['email'] = $request->email;
            $updateArray['mobile_no'] = $request->mobile_no;
        }
        HealthQuote::where('uuid',$id)->update($updateArray);

        if (isset($request->return_to_view))
            return redirect("quote/health/" . $id)->with('success', 'Health Quote has been updated');
    }

    public function getLeads($CDBID, $email, $mobile_no, $lead_type)
    {
        $query = DB::table('health_quote_request as hqr')
                    ->select('hqr.id','hqr.uuid','hqr.first_name','hqr.last_name','hqr.created_at','u.name AS advisor_name',DB::raw("'Health' as lead_type")
                    ,'u.id as advisor_id','qs.text as lead_status')
                    ->leftJoin('users as u', 'u.id', '=', 'hqr.advisor_id')
                    ->leftJoin('quote_status as qs', 'qs.id', '=', 'hqr.quote_status_id')
                    ->orderBy('advisor_id', 'ASC');
        if (!empty($CDBID)) {
            $query->where('hqr.id', '=', $CDBID);
        }
        if (!empty($email)) {
            $query->where('hqr.email', '=', $email);
        }
        if (!empty($mobile_no)) {
            $query->where('hqr.mobile_no', '=', $mobile_no);
        }
        return $query;
    }

    public function fillModelProperties()
    {
        return array(
            "id" => "readonly|none",
            "first_name" => "input|text|required",
            "last_name" => "input|text|required",
            "preference" => "input|text",
            "code" => "input|title",
            "created_at" => "input|date|title",
            "updated_at" => "input|date|title",
            "email" => "input|email|required",
            "details" => "input|text",
            "mobile_no" => "input|title|number|required",
            "source" => "input|text|title",
            "dob" => 'input|date|required',
            "marital_status_id" => "select|title|required",
            "cover_for_id" => "select|title|required",
            "nationality_id" => "select|title|required",
            "advisor_id" => "select|title|required",
            "quote_status_id" => "select|title|required",
            "has_dental" => "input|checkbox|title",
            "has_worldwide_cover" => "input|checkbox|title",
            "has_home" => "input|checkbox|title",
            "emirate_of_your_visa_id" => "select|title|required"
        );
    }

    public function getCustomTitleByProperty($propertyName)
    {
        $title = "";
        switch ($propertyName) {
            case 'marital_status_id':
                $title = "Marital Status";
                break;
            case 'code':
                $title = "CDB ID";
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
            case 'advisor_id':
                $title = "Advisor";
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
            case 'quote_status_id':
                $title = "Lead Status";
                break;
            case 'updated_at':
                $title = "Last Modified Date";
                break;
            case 'created_at':
                $title = "Created Date";
                break;
            default:
                break;
        }
        return $title;
    }

    public function fillModelSkipProperties()
    {
        return [
            "create" => "id,advisor_id,code",
            "list" => "email,cover_for_id,has_worldwide_cover,has_home,details,preference,mobile_no,dob,marital_status_id,nationality_id,source,has_dental,emirate_of_your_visa_id",
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id','advisor_id','created_at'];
    }
}
