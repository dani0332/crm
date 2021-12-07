<?php

namespace App\Services;

use App\Models\HomeQuote;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Auth;

class HomeQuoteService extends BaseService
{
    protected $query;

    public function __construct()
    {

        $this->query = DB::table('home_quote_request as hqr')->
        select('hqr.id','hqr.uuid','hqr.first_name','hqr.last_name','hqr.email','hqr.mobile_no','hqr.address','hqr.has_contents','hqr.contents_aed'
        ,'hqr.has_personal_belongings','hqr.personal_belongings_aed','hqr.has_building','hqr.building_aed','hqr.ilivein_accommodation_type_id'
        ,'hat.TEXT AS ilivein_accommodation_type_id_text','hqr.iam_possesion_type_id','hpt.TEXT AS iam_possesion_type_id_text')
        ->leftJoin('home_accommodation_type as hat', 'hat.id', '=', 'hqr.ilivein_accommodation_type_id')
        ->leftJoin('home_possession_type as hpt', 'hpt.id', '=', 'hqr.iam_possesion_type_id');
    }

    public function getEntity($id)
    {
        return $this->query->where('hqr.uuid', $id)->first();
    }

    public function saveHomeQuote(Request $request)
    {
        $dataArr = array(
            "firstName" => $request->first_name,
            "lastName" => $request->last_name,
            "email" => $request->email,
            "address" => $request->address,
            "mobileNo" => $request->mobile_no,
            "contentsAed" => $request->contents_aed,
            "iamPossesionTypeId" => $request->iam_possesion_type_id,
            "iliveinAccommodationTypeId" => $request->ilivein_accommodation_type_id,
            "personalBelongingsAed" => $request->personal_belongings_aed,
            "buildingAed" => $request->building_aed,
            "hasContents" => $request->has_contents == 'on' ?  true : false,
            "nationalityId" => $request->nationality_id,
            "hasBuilding" => $request->has_building == 'on' ? true : false,
            "hasPersonalBelongings" => $request->has_personal_belongings == 'on' ?  true : false,
        );
        if(Auth::user()->hasRole("HOME_ADVISOR")) $dataArr['advisorId'] = Auth::users()->id;
        return CapiRequestService::sendCAPIRequest('/api/v1-save-home-quote', $dataArr);
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
            case 'ilivein_accommodation_type':
                return 'hat';
                break;
            case 'iam_possesion_type':
                return 'hpt';
                break;
            default:
                return 'hqr';
                break;
        }
    }

    public function getLeadsForAssignment()
    {
        return HomeQuote::orderBy('created_at', 'desc')->get();
    }

    public function getLeads($CDBID, $email, $mobile_no, $lead_type)
    {
        $query = DB::table('home_quote_request as hqr')
                    ->select('hqr.id','hqr.uuid','hqr.first_name','hqr.last_name','hqr.created_at','u.name AS advisor_name',DB::raw("'Home' as lead_type")
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

    public function updateHomeQuote(Request $request, $id)
    {
        $updateArray = [
            'first_name'=>$request->first_name,
            'last_name'=>$request->last_name,
            'address'=>$request->address,
            'contents_aed' => $request->contents_aed,
            'iam_possesion_type_id' => $request->iam_possesion_type_id,
            'ilivein_accommodation_type_id' => $request->ilivein_accommodation_type_id,
            'personal_belongings_aed' => $request->personal_belongings_aed,
            'building_aed' => $request->building_aed,
            'has_contents' => $request->has_contents == 'on' ?  true : false,
            'nationality_id' => $request->nationality_id,
            'has_building' => $request->has_building == 'on' ? true : false,
            'has_personal_belongings' => $request->has_personal_belongings == 'on' ?  true : false,
        ];
        if(!Auth::user()->hasRole('HOME_ADVISOR')){
            $updateArray['email'] = $request->email;
            $updateArray['mobile_no'] = $request->mobile_no;
        }
        HomeQuote::where('uuid',$id)->update($updateArray);

        if (isset($request->return_to_view))
            return redirect("quote/home/" . $id)->with('success', 'Home Quote has been updated');
    }

    public function fillModelProperties()
    {
        return array(
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

    public function getCustomTitleByProperty($propertyName)
    {
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

    public function fillModelSkipProperties()
    {
        return [
            "create" => "id",
            "list" => "email,address,iam_possesion_type_id,ilivein_accommodation_type_id",
        ];
    }

    public function fillModelSearchProperties()
    {
        return ["email", 'first_name', 'last_name', 'iam_possesion_type_id', 'ilivein_accommodation_type_id'];
    }

    public function getValidationArray($modelPropertiesList, $request)
    {
        $validationArray = [];
        foreach ($modelPropertiesList as $propertyName => $propertyValue) {

            if ($propertyName == 'contents_aed' || $propertyName ==  'personal_belongings_aed' || $propertyName == 'building_aed' || $propertyName == 'has_contents' || $propertyName == 'has_personal_belongings' || $propertyName == 'has_building') {
                if ($request['iam_possesion_type_id'] == null) {
                    $validationArray['has_contents'] = 'required';
                }
                if ($request['iam_possesion_type_id'] == "1") {
                    if ($request['has_building'] == null) {
                        $validationArray['has_contents'] = 'required';
                    }
                    if ($request['has_contents'] == null) {
                        $validationArray['has_building'] = 'required';
                    }
                    if ($request['has_contents'] == 'on') {
                        $validationArray['contents_aed'] = 'required';
                    }
                    if ($request['has_building'] == 'on') {
                        $validationArray['building_aed'] = 'required';
                    }
                    if ($request['has_personal_belongings'] == 'on') {
                        $validationArray['personal_belongings_aed'] = 'required';
                    }
                }

                if ($request['iam_possesion_type_id'] == "2") {
                    $validationArray['has_contents'] = 'required';
                    if ($request['has_contents'] == 'on') {
                        $validationArray['contents_aed'] = 'required';
                    }

                    if ($request['has_personal_belongings'] == 'on') {
                        $validationArray['personal_belongings_aed'] = 'required';
                    }
                }
            } else {
                if ($propertyName != 'id') {
                    $validationArray[$propertyName] = 'required';
                }
            }
        }
        return $validationArray;
    }

    public function getEntityPlain($id)
    {
        return HomeQuote::where('id', $id)->first();
    }
}
