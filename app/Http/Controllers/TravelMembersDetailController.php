<?php

namespace App\Http\Controllers;

use App\Http\Requests\TravelMemberDetailRequest;
use App\Models\TravelMemberDetail;
use App\Models\TravelQuote;
use Carbon\Carbon;

class TravelMembersDetailController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(TravelMemberDetailRequest $request)
    {
        TravelMemberDetail::create($request->validated());
        TravelQuote::find($request->travel_quote_request_id)->update(['quote_updated_at' => Carbon::now()]);

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
        $data = TravelMemberDetail::find($id);

        return view('members/travel/edit', compact('data'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(TravelMemberDetailRequest $request, $id)
    {
        TravelMemberDetail::findOrFail($id)->update($request->validated());
        TravelQuote::find($request->travel_quote_request_id)->update(['quote_updated_at' => Carbon::now()]);

        $travelhMemberData = $request->only(['dob', 'nationality_id']);
        TravelQuote::where('primary_member_id', $id)->update($travelhMemberData);

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
        $data = TravelMemberDetail::find($id);
        if ($data) {
            TravelQuote::find($data->travel_quote_request_id)->update(['quote_updated_at' => Carbon::now(), 'primary_member_id' => null]);
            $data->delete();
        }

        return redirect()->back();
    }
}
