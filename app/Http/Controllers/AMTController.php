<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use DataTables;

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
            ->join('business_quote_request_detail as bqrd', 'bqr.id', '=', 'bqrd.business_quote_request_id')
            ->join('business_type_of_insurance as bit', 'bqr.business_type_of_insurance_id', '=', 'bit.id')
            ->join('quote_status as qs', 'bqr.quote_status_id', '=', 'qs.id')
            ->where('bit.text', '=', 'Group Medical')
            ->select('bqr.id', 'bqr.code', 'bqr.uuid as uuid', 'bqr.first_name', 'bqr.last_name', 'qs.text as leadStatus', 'bqr.created_at', 'bqr.updated_at', 'bit.text as leadType');

        if ($request->ajax()) {
            if (isset($request->first_name) && $request->first_name != '') {
                $data = $data->where('bqr.first_name', 'like', '%' . $request->first_name . '%');
            }
            if (isset($request->last_name) && $request->last_name != '') {
                $data = $data->where('bqr.last_name', 'like', '%' . $request->last_name . '%');
            }
            if (isset($request->code) && $request->code != '') {
                $data = $data->where('bqr.code', '=', $request->code);
            }
            if (isset($request->leadStatus) && $request->leadStatus != '') {
                $data = $data->where('qs.id', '=', $request->leadStatus);
            }
            return DataTables::of($data)
                ->addIndexColumn()
                ->make(true);
            return view('amt.view');
        }
        return view('amt.view');
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
