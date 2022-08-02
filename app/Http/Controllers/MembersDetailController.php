<?php

namespace App\Http\Controllers;

use App\Http\Requests\MemberDetail;
use App\Models\HealthMemberDetail;
use App\Models\HealthQuote;
use App\Services\LookupService;
use Carbon\Carbon;

class MembersDetailController extends Controller
{

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
            'salary_band_id' => $request->salary_band,
        ];
        HealthMemberDetail::create($data);
        HealthQuote::find($request->health_quote_request_id)->update(['quote_updated_at' => Carbon::now()]);

        return redirect()->back();
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

        return view('members/edit', compact('data', 'categories', 'salaries'));
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
            'salary_band_id' => $request->salary_band,
        ];
        HealthMemberDetail::find($id)->update($data);
        HealthQuote::find($request->health_quote_request_id)->update(['quote_updated_at' => Carbon::now()]);

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
        $data = HealthMemberDetail::find($id);
        if ($data) {
            HealthQuote::find($data->health_quote_request_id)->update(['quote_updated_at' => Carbon::now(), 'primary_member_id' => null]);
            $data->delete();
        }

        return redirect()->back();
    }
}
