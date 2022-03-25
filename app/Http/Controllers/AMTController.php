<?php

namespace App\Http\Controllers;

use App\Models\BusinessInsuranceType;
use App\Models\BusinessQuote;
use App\Models\BusinessQuoteRequestDetail;
use App\Models\GroupMedicalType;
use App\Models\QuoteStatus;
use App\Models\User;
use App\Services\BusinessQuoteService;
use App\Services\CRUDService;
use Illuminate\Http\Request;
use DB;
use DataTables;
use \Carbon\Carbon;
use Auth;
use Illuminate\Support\Facades\Redirect;

class AMTController extends Controller
{
    protected $businessQuoteService;
    protected $crudService;
    public function __construct(BusinessQuoteService $businessQuoteService, CRUDService $crudService)
    {
        $this->businessQuoteService = $businessQuoteService;
        $this->crudService = $crudService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $data = DB::table('business_quote_request as bqr')
            ->leftJoin('business_quote_request_detail as bqrd', 'bqr.id', '=', 'bqrd.business_quote_request_id')
            ->leftJoin('business_type_of_insurance as bit', 'bqr.business_type_of_insurance_id', '=', 'bit.id')
            ->leftJoin('users as u', 'bqr.advisor_id', '=', 'u.id')
            ->leftJoin('lost_reasons as ls', 'ls.id', '=', 'bqrd.lost_reason_id')
            ->leftJoin('quote_status as qs', 'bqr.quote_status_id', '=', 'qs.id')
            ->where('bit.text', '=', 'Group Medical')
            ->select(
                'bqr.id',
                'bqr.code',
                'bqr.uuid as uuid',
                'bqr.first_name',
                'bqr.last_name',
                'qs.text as leadStatus',
                'bqr.created_at',
                'bqr.updated_at',
                'bit.text as leadType',
                'bqr.advisor_id',
                'bqr.source',
                'ls.text as lost_reason',
                'u.name as advisor_id_text',
                'bqr.premium',
                'bqr.company_name',
                'bqrd.next_followup_date',
            )->orderBy('bqr.advisor_id', 'asc');

        if (Auth::user()->isAdvisor()) {
            $data = $data->where('bqr.advisor_id', Auth::user()->id);
        }

        $leadStatuses = DB::table('quote_status')->select('id', 'text')->orderBy('sort_order', 'asc')->get();
        $advisors = DB::table('users as u')
            ->join('model_has_roles as mr', 'mr.model_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->whereIn('r.name', ['GM_ADVISOR'])
            ->select('u.id', DB::raw("CONCAT(u.name,' - ',r.name) AS name"))->orderBy('r.name')->distinct()->get();
        $isManagerORDeputy = Auth::user()->isManagerORDeputy();
        $model = 'Business';
        if ($request->ajax()) {

            if (isset($request->first_name) && $request->first_name != '') {
                $data->where('bqr.first_name', 'like', '%' . $request->first_name . '%');
            }
            if (isset($request->created_at_start) && $request->created_at_start != '' && isset($request->created_at_end) && $request->created_at_end != '') {
                $dateFrom = Carbon::createFromFormat('Y-m-d', $request->created_at_start)->startOfDay()->toDateTimeString();
                $dateTo = Carbon::createFromFormat('Y-m-d', $request->created_at_end)->endOfDay()->toDateTimeString();
                $data->whereBetween('bqr.created_at', [$dateFrom, $dateTo]);
            }
            if (isset($request->last_name) && $request->last_name != '') {
                $data->where('bqr.last_name', 'like', '%' . $request->last_name . '%');
            }
            if (isset($request->email) && $request->email != '') {
                $data->where('bqr.email', 'like', '%' . $request->email . '%');
            }
            if (isset($request->code) && $request->code != '') {
                $data->where('bqr.code', '=', $request->code);
            }
            if (isset($request->mobile_no) && $request->mobile_no != '') {
                $data->where('bqr.mobile_no', '=', $request->mobile_no);
            }
            if (isset($request->leadStatus) && $request->leadStatus != '') {
                $data->where('qs.id', '=', $request->leadStatus);
            }
            if (isset($request->advisor_id) && $request->advisor_id != '') {
                $request->advisor_id == '-1' ? $data->whereNull('bqr.advisor_id') : $data->where('bqr.advisor_id', '=', $request->advisor_id);
            }
            $column = $request->get('order') != null ? $request->get('order')[0]['column'] : '';
            $direction = $request->get('order') != null ? $request->get('order')[0]['dir'] : '';
            if ($column != '' && $column != 0 && $direction != '') {
                if ($column == 11) {
                    $column = "bqr.created_at";
                }
                if ($column == 12) {
                    $column = "bqr.updated_at";
                }
                if ($column == 8) {
                    $column = "bqrd.next_followup_date";
                }
                $data->orderBy($column, $direction);
            } else {
                $data->orderBy('bqr.created_at', 'DESC');
            }
            return DataTables::of($data)
                ->addIndexColumn()
                ->make(true);
            return view('amt.view', compact('model', 'leadStatuses', 'advisors', 'isManagerORDeputy'));
        }
        return view('amt.view', compact('model', 'leadStatuses', 'advisors', 'isManagerORDeputy'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $businessInsuranceType = BusinessInsuranceType::select('id', 'text')->where('text', 'Group Medical')->get();
        return view('amt.add', compact('businessInsuranceType'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'first_name' => 'required|max:150',
            'last_name' => 'required|max:150',
            'email' => 'required|email',
            'mobile_no' => 'required',
            'business_type_of_insurance_id' => 'required',
            'company_name' => 'required|max:150',
            'number_of_employees' => 'required',
            "brief_details" => "required",
        ]);
        $record = $this->businessQuoteService->saveBusinessQuote($request);
        if (isset($record->message) && str_contains($record->message, 'Error')) {
            return Redirect::back()->with('message', $record->message)->withInput();
        } else {
            if (!isset($record->quoteUID)) {
                return redirect('medical/amt')->with('success', 'Lead has been stored');
            } else {
                return redirect('medical/amt/' . $record->quoteUID)->with('success', 'Lead has been stored');
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $businessInsuranceType = BusinessInsuranceType::select('id', 'text')->where('text', 'Group Medical')->get();
        $record = BusinessQuote::where([['uuid', $id], ['business_type_of_insurance_id', 5]])->first();
        $leadStatuses = DB::table('quote_status')
            ->select('id', 'text')
            ->whereNotIn('text', [
                'AML Screening Cleared', 'Draft', 'Cancelled', 'AML Screening Failed', 'Transaction Declined', 'Policy Issued', 'Policy Invoiced',
                'Completed', 'Pending', 'Rejected', 'Issued', 'Approved', 'Approval required', 'Resubmit for approval'
            ])
            ->orderBy('sort_order', 'asc')->get();
        $lostReasons = DB::table('lost_reasons')
            ->select('id', 'text')
            ->get();
        $selectedLostReasonId = $this->crudService->getSelectedLostReason('business', $record->id);;

        $selectedLeadStatus = '';
        if (isset($record->quote_status_id) && $record->quote_status_id != '') {
            $selectedLeadStatus  = QuoteStatus::where('id', $record->quote_status_id)->first();
        }
        $assignedUserName = '';
        $assignedGMType = '';
        if (isset($record->group_medical_type_id) &&  $record->group_medical_type_id != '') {
            $assignedGMType = GroupMedicalType::where('id', $record->group_medical_type_id)->first()->text;
        }
        if (isset($record->advisor_id) && $record->advisor_id != '') {
            $assignedUser = User::where('id', $record->advisor_id)->first();
            $assignedUserName = $assignedUser->name;
        }
        $modeltype = 'business';
        $advisors = DB::table('users as u')
            ->join('model_has_roles as mr', 'mr.model_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->whereIn('r.name', ['RM_ADVISOR', 'GM_ADVISOR'])
            ->select('u.id', DB::raw("CONCAT(u.name,' - ',r.name) AS name"))->orderBy('r.name')->distinct()->get();

        if ($selectedLeadStatus != '') {
            $selectedLeadStatus = $selectedLeadStatus->text;
        }
        return view('amt.show', compact('businessInsuranceType', 'record', 'selectedLeadStatus', 'advisors', 'assignedUserName', 'assignedGMType', 'leadStatuses', 'lostReasons', 'selectedLostReasonId', 'modeltype'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $businessInsuranceType = BusinessInsuranceType::select('id', 'text')->where('text', 'Group Medical')->get();
        $record = BusinessQuote::where([['uuid', $id], ['business_type_of_insurance_id', 5]])->first();
        $gmTypes = GroupMedicalType::select('id', 'text', 'description')->get();
        $GMType = DB::table('business_quote_request')
            ->join('group_medical_types as gmt', 'business_quote_request.group_medical_type_id', '=', 'gmt.id')
            ->where('business_quote_request.uuid', $id)
            ->select('gmt.text as text')
            ->first();
        $selectedGmType = '';
        if (!is_null($GMType)) {
            $selectedGmType = $GMType->text;
        }
        return view('amt.edit', compact('businessInsuranceType', 'record', 'gmTypes', 'selectedGmType'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'first_name' => 'required|max:150',
            'last_name' => 'required|max:150',
            'business_type_of_insurance_id' => 'required',
            'company_name' => 'required|max:150',
            'number_of_employees' => 'required',
            "brief_details" => "required",
            "group_medical_type_id" => "required",
            "premium" => "required",
        ]);
        $this->crudService->updateModelByType('business', $request, $id);
        return redirect('medical/amt/' . $id)->with('success', 'Lead has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
