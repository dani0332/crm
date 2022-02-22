<?php

namespace App\Services;

use App\Models\HomeQuote;
use App\Models\HomeQuoteRequestDetail;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Auth;
use \Carbon\Carbon;
use Config;

class HomeQuoteService extends BaseService
{
    protected $query;

    public function __construct()
    {

        $this->query = DB::table('home_quote_request as hqr')->select(
            'hqr.id',
            'hqr.code',
            'hqr.uuid',
            'hqr.first_name',
            'hqr.last_name',
            'hqr.email',
            'hqr.mobile_no',
            'hqr.address',
            'hqr.has_contents',
            'hqr.contents_aed',
            'hqr.has_personal_belongings',
            'hqr.personal_belongings_aed',
            'hqr.has_building',
            'hqr.building_aed',
            'hqr.source',
            'hqr.ilivein_accommodation_type_id',
            'hqr.quote_status_id',
            'qs.text as quote_status_id_text',
            'hqr.created_at',
            'hqr.updated_at',
            'hqr.premium',
            'hqr.advisor_id',
            'u.name as advisor_id_text',
            'hat.TEXT AS ilivein_accommodation_type_id_text',
            'hqr.iam_possesion_type_id',
            'hpt.TEXT AS iam_possesion_type_id_text',
            'hqrd.next_followup_date',
            'hqrd.notes',
            'ls.text as lost_reason',
        )
            ->leftJoin('home_quote_request_detail as hqrd', 'hqrd.home_quote_request_id', '=', 'hqr.id')
            ->leftJoin('lost_reasons as ls', 'ls.id', '=', 'hqrd.lost_reason_id')
            ->leftJoin('home_accommodation_type as hat', 'hat.id', '=', 'hqr.ilivein_accommodation_type_id')
            ->leftJoin('home_possession_type as hpt', 'hpt.id', '=', 'hqr.iam_possesion_type_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'hqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'hqr.advisor_id')
            ->where('qs.text', '!=', 'Fake');
    }

    public function getEntity($id)
    {
        return $this->query->where('hqr.uuid', $id)->first();
    }

    public function getSelectedLostReason($id)
    {
        $entity = HomeQuoteRequestDetail::where('home_quote_request_id', $id)->first();
        $lostId = 0;
        if (!is_null($entity) && $entity->lost_reason_id) {
            $lostId = $entity->lost_reason_id;
        }
        return $lostId;
    }

    public function getDetailEntity($id)
    {
        $entity = HomeQuoteRequestDetail::where('home_quote_request_id', $id)->first();
        if (!$entity) {
            HomeQuoteRequestDetail::create([
                'home_quote_request_id' => $id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
        return HomeQuoteRequestDetail::where('home_quote_request_id', $id)->first();
    }

    public function saveHomeQuote(Request $request)
    {
        $sourceName = Config::get('constants.SOURCE_NAME');
        $appUrl = Config::get('constants.APP_URL');
        $dataArr = array(
            "firstName" => $request->first_name,
            "lastName" => $request->last_name,
            "email" => $request->email,
            "address" => $request->address,
            "mobileNo" => $request->mobile_no,
            "contentsAed" => $request->contents_aed,
            "premium" => $request->premium,
            "iamPossesionTypeId" => $request->iam_possesion_type_id,
            "iliveinAccommodationTypeId" => $request->ilivein_accommodation_type_id,
            "personalBelongingsAed" => $request->personal_belongings_aed,
            "buildingAed" => $request->building_aed,
            "hasContents" => $request->has_contents == 'on' ?  true : false,
            "nationalityId" => $request->nationality_id,
            "hasBuilding" => $request->has_building == 'on' ? true : false,
            "hasPersonalBelongings" => $request->has_personal_belongings == 'on' ?  true : false,
            "source" => $sourceName,
            "referenceUrl" => $appUrl,
        );
        if (Auth::user()->hasRole("HOME_ADVISOR")) $dataArr['advisorId'] = Auth::user()->id;
        return CapiRequestService::sendCAPIRequest('/api/v1-save-home-quote', $dataArr);
    }

    public function getGridData($model, $request)
    {
        $searchProperties = $model->searchProperties;
        if ($request->ajax()) {
            if (isset($request->assigned_to_date_start) && $request->assigned_to_date_start != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['assigned_to_date_start'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['assigned_to_date_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('hqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
            }
            if (in_array('created_at', $searchProperties) && isset($request->created_at) && $request->created_at != "") {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['created_at'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['created_at_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('hqr.created_at', [$dateFrom, $dateTo]);
            }
            if (isset($request->next_followup_date) && $request->next_followup_date != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request['next_followup_date'])->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request['next_followup_date_end'])->endOfDay()->toDateTimeString();
                $this->query->whereBetween('hqrd.next_followup_date', [$dateFrom, $dateTo]);
            }
            foreach ($searchProperties as $item) {
                if (!empty($request[$item]) && $item != "created_at") {
                    if ($request[$item] == 'null') {
                        $this->query->whereNull($item);
                    } else {
                        $this->query->where($this->getQuerySuffix($item) . '.' . $item, $request[$item]);
                    }
                }
            }
        }
        $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
        $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
        if ($column != '' && $column != 0 && $direction != '') {
            $isManagerORDeputy = Auth::user()->isManagerOrDeputy();
            $isAdmin = Auth::user()->hasRole("ADMIN");
            if ($isAdmin || $isManagerORDeputy == "1") {
                if ($column == 6) {
                    $column = "hqr.created_at";
                }
                if ($column == 7) {
                    $column = "hqr.updated_at";
                }
                if ($column == 8) {
                    $column = "hqrd.next_followup_date";
                }
            } else {
                if ($column == 5) {
                    $column = "hqr.created_at";
                }
                if ($column == 6) {
                    $column = "hqr.updated_at";
                }
                if ($column == 7) {
                    $column = "hqrd.next_followup_date";
                }
            }
            return $this->query->orderBy($column, $direction);
        } else {
            return $this->query->orderBy('hqr.created_at', 'DESC');
        }
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
            case 'advisor':
                return 'u';
                break;
            case 'quote_status':
                return 'qs';
                break;
            default:
                return 'hqr';
                break;
        }
    }

    public function getHomeLeadsForAdvisor($request)
    {
        $query = DB::table('home_quote_request as hqr')
            ->select(
                'hqr.id',
                'hqr.uuid',
                'hqr.code',
                DB::raw("CONCAT_WS(' ',hqr.first_name,hqr.last_name) AS clientName"),
                'qs.text as leadStatus',
                'hqr.created_at as createdAt',
                'hqr.quote_status_id',
                'hqrd.advisor_assigned_date as assignedDate',
                'u.name as assignedBy',
                'hqr.updated_at',
                'hqr.source as leadSource',
                'hqrd.next_followup_date as nextFollowupDate',
            )
            ->leftJoin('home_quote_request_detail as hqrd', 'hqrd.home_quote_request_id', '=', 'hqr.id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'hqr.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'hqrd.advisor_assigned_by_id')
            ->where('qs.text', '!=', 'Fake')
            ->where('hqr.advisor_id', Auth::user()->id);
        $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
        $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
        if ($column != '' && $column != 0 && $direction != '') {
            if ($column == 3) {
                $column = "hqr.created_at";
            }
            if ($column == 4) {
                $column = "hqrd.advisor_assigned_date";
            }
            if ($column == 7) {
                $column = "hqrd.next_followup_date";
            }
            $query->orderBy($column, $direction);
        }
        if (isset($request->startedAt) && isset($request->endAt) && $request->startedAt != '' && $request->endAt != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request->startedAt)->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request->endAt)->endOfDay()->toDateTimeString();
            $query->whereBetween('hqrd.advisor_assigned_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->nfdSart) && isset($request->nfdEnd) && $request->nfdSart != '' && $request->nfdEnd != '') {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request->nfdSart)->startOfDay()->toDateTimeString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $request->nfdEnd)->endOfDay()->toDateTimeString();
            $query->whereBetween('hqrd.next_followup_date', [$dateFrom, $dateTo]);
        }
        if (isset($request->cdbId) && $request->cdbId != 0) {
            $query->where('hqr.code', $request->cdbId);
        }
        if (isset($request->email) && $request->email != '') {
            $query->where('hqr.email', $request->email);
        }
        if (isset($request->leadStatus) && $request->leadStatus != 0) {
            $query->where('hqr.quote_status_id', $request->leadStatus);
        }
        return $query;
    }

    public function getLeadsForAssignment()
    {
        return HomeQuote::orderBy('created_at', 'desc')->get();
    }

    public function updateChildRecord($id)
    {
        $childRecord = HomeQuoteRequestDetail::where('home_quote_request_id', $id)->first();
        if (!empty($childRecord)) {
            $childRecord->advisor_assigned_by_id = Auth::user()->id;
            $childRecord->advisor_assigned_date = Carbon::now();
            $childRecord->save();
        }
    }

    public function getLeads($CDBID, $email, $mobile_no, $lead_type)
    {
        $query = DB::table('home_quote_request as hqr')
            ->select(
                'hqr.id',
                'hqr.uuid',
                'hqr.first_name',
                'hqr.last_name',
                'hqr.code',
                'hqr.created_at',
                'u.name AS advisor_name',
                DB::raw("'Home' as lead_type"),
                'u.id as advisor_id',
                'qs.text as lead_status'
            )
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
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'address' => $request->address,
            'contents_aed' => $request->contents_aed,
            'iam_possesion_type_id' => $request->iam_possesion_type_id,
            'ilivein_accommodation_type_id' => $request->ilivein_accommodation_type_id,
            'personal_belongings_aed' => $request->personal_belongings_aed,
            'building_aed' => $request->building_aed,
            'has_contents' => $request->has_contents == 'on' ?  true : false,
            'nationality_id' => $request->nationality_id,
            'premium' => $request->premium,
            'has_building' => $request->has_building == 'on' ? true : false,
            'has_personal_belongings' => $request->has_personal_belongings == 'on' ?  true : false,
        ];
        if (!Auth::user()->hasRole('HOME_ADVISOR')) {
            $updateArray['email'] = $request->email;
            $updateArray['mobile_no'] = $request->mobile_no;
        }
        HomeQuote::where('uuid', $id)->update($updateArray);

        if (isset($request->return_to_view))
            return redirect("quote/home/" . $id)->with('success', 'Home Quote has been updated');
    }

    public function fillModelProperties()
    {
        return array(
            "id" => "readonly|none",
            "code" => "input|title",
            "first_name" => "input|text|required",
            "last_name" => "input|text|required",
            "email" => "input|email|required",
            "mobile_no" => "input|title|number|required",
            "quote_status_id" => "select|title",
            "advisor_id" => "select|title",
            "created_at" => "input|date|title|range",
            "updated_at" => "input|date|title",
            "next_followup_date" => "input|date|title|range",
            "source" => "input|text|required",
            "lost_reason" => "input|text",
            "premium" => "input|number|required",
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
            case 'created_at':
                $title = "Created Date";
                break;
            case 'updated_at':
                $title = "Last Modified Date";
                break;
            case 'created_at_end':
                $title = "End Date";
                break;
            case 'quote_status_id':
                $title = "Lead Status";
                break;
            case 'advisor_id':
                $title = "Assigned To";
                break;
            case 'ilivein_accommodation_type_id':
                $title = "I Live In";
                break;
            case 'mobile_no':
                $title = "Mobile Number";
                break;
            case 'code':
                $title = "CDB ID";
                break;
            case 'next_followup_date':
                $title = "Next Followup Date";
                break;
            default:
                break;
        }
        return $title;
    }

    public function fillModelSkipProperties()
    {
        return [
            "create" => "id,code,quote_status_id,advisor_id,created_at,updated_at,next_followup_date,lost_reason",
            "list" => "email,address,iam_possesion_type_id,ilivein_accommodation_type_id,mobile_no,personal_belongings_aed,building_aed,contents_aed,has_contents,has_personal_belongings,has_building,address",
            "update" => "id,code,quote_status_id,advisor_id,created_at,updated_at,next_followup_date,lost_reason",
            "show" => "id,next_followup_date,lost_reason",
        ];
    }

    public function fillModelSearchProperties()
    {
        return ['code', 'first_name', 'last_name', 'email', 'mobile_no', 'quote_status_id', 'advisor_id', 'created_at', 'next_followup_date'];
    }

    public function getValidationArray($modelPropertiesList, $request, $modelSkipPropertiesList)
    {
        $validationArray = [];
        $skipProperties = explode(',', $modelSkipPropertiesList);
        foreach ($modelPropertiesList as $propertyName => $propertyValue) {
            if (in_array($propertyName, $skipProperties))
                continue;
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
                if ($propertyName != 'id' && $propertyName != 'email' && $propertyName != 'code'  && $propertyName != 'created_at' && $propertyName != 'updated_at' && $propertyName != 'mobile_no' && $propertyName != 'quote_status_id' && $propertyName != 'next_followup_date' && $propertyName != 'lost_reason' && $propertyName != 'source' && $propertyName != 'advisor_id') {
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
