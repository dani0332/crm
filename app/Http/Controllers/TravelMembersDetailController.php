<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TravelMemberDetail;
use App\Models\TravelQuote;
use Carbon\Carbon;
class TravelMembersDetailController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = [
            'travel_quote_request_id' => $request->travel_quote_request_id,
            'gender' => $request->gender,
            'dob' => $request->dob
        ];
        TravelMemberDetail::create($data);
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
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = [
            'travel_quote_request_id' => $request->travel_quote_request_id,
            'gender' => $request->gender,
            'dob' => $request->dob
        ];
        TravelMemberDetail::find($id)->update($data);
        TravelQuote::find($request->travel_quote_request_id)->update(['quote_updated_at' => Carbon::now()]);
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
        if($data) {
            TravelQuote::find($data->travel_quote_request_id)->update(['quote_updated_at' => Carbon::now(),'primary_member_id' => null]);
            $data->delete();
        }
        return redirect()->back();
    }
}
