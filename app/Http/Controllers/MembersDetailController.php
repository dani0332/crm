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
        // $dob = isset($request->dob) ? Carbon::createFromFormat('d-m-Y', $request->dob)->format(get_dob_date_format()) : null;
        $data = [
            'health_quote_request_id' => $request->health_quote_request_id,
            'gender' => $request->gender,
            'dob' => $request->dob,
            'nationality_id' => $request->nationality_id,
            'emirate_of_your_visa_id' => $request->emirate_of_your_visa_id,
            'member_category_id' => $request->member_category_id,
            'salary_band_id' => $request->salary_band_id,
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
        // $dob = isset($request->dob) ? Carbon::createFromFormat('d-m-Y', $request->dob)->format(get_dob_date_format()) : null;
        $data = [
            'health_quote_request_id' => isset($request->health_quote_request_id) ? $request->health_quote_request_id : null,
            'gender' => isset($request->gender) ? $request->gender : null,
            'dob' => isset($request->dob) ? $request->dob : null,
            'nationality_id' => isset($request->nationality_id) ? $request->nationality_id : null,
            'emirate_of_your_visa_id' => isset($request->emirate_of_your_visa_id) ? $request->emirate_of_your_visa_id : null,
            'member_category_id' => isset($request->member_category_id) ? $request->member_category_id : null,
            'salary_band_id' => isset($request->salary_band_id) ? $request->salary_band_id : null,
        ];

        $memberDetail = HealthMemberDetail::find($id);

        if ($memberDetail) {
            $memberDetail->update($data);
            HealthQuote::find($request->health_quote_request_id)->update(['quote_updated_at' => Carbon::now()]);
            unset($data['health_quote_request_id']);
            HealthQuote::where('primary_member_id', $memberDetail->id)->update($data);
        }

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
