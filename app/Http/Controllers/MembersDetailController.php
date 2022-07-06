<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\MemberDetail;
use App\Models\HealthMemberDetail;
use App\Services\LookupService;
class MembersDetailController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
    public function store(MemberDetail $request)
    {
        $data = [
            'health_quote_request_id' => $request->health_quote_request_id,
            'gender' => $request->gender,
            'dob' => $request->dob,
            'member_category_id' => $request->member_category,
            'salary_band_id' => $request->salary_band
        ];
        HealthMemberDetail::create($data);
        return redirect()->back();
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
        $data = HealthMemberDetail::find($id);
        $lookUpService = new LookupService();
        $categories = $lookUpService->getMemberCategories($id);
        $salaries = $lookUpService->getSalaryBands($id);
        return view('members/edit', compact('data','categories','salaries'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(MemberDetail $request, $id)
    {
        $data = [
            'health_quote_request_id' => $request->health_quote_request_id,
            'gender' => $request->gender,
            'dob' => $request->dob,
            'member_category_id' => $request->member_category,
            'salary_band_id' => $request->salary_band
        ];
        HealthMemberDetail::find($id)->update($data);
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        HealthMemberDetail::find($id)->delete();
        return redirect()->back();
    }
}
