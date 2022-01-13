<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use DataTables;
use \Carbon\Carbon;
use Auth;

class AMTController extends Controller
{
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
                'u.name as advisor_id_text',
                'bqr.premium',
                'bqr.company_name'
            )->orderBy('bqr.advisor_id', 'asc');

        if (Auth::user()->isAdvisor()) {
            $data = $data->where('bqr.advisor_id', Auth::user()->id);
        }

        $leadStatuses = DB::table('quote_status')->select('id', 'text')->get();
        $advisors = DB::table('users as u')
            ->join('model_has_roles as mr', 'mr.model_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->whereIn('r.name', ['BUSINESS_ADVISOR'])
            ->select('u.id', DB::raw("CONCAT(u.name,' - ',r.name) AS name"))->orderBy('r.name')->distinct()->get();
        $isManagerORDeputy = Auth::user()->isManagerORDeputy();
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
            return DataTables::of($data)
                ->addIndexColumn()
                ->make(true);
            return view('amt.view', compact('leadStatuses', 'advisors', 'isManagerORDeputy'));
        }
        return view('amt.view', compact('leadStatuses', 'advisors', 'isManagerORDeputy'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
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
