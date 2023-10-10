<?php

namespace App\Http\Controllers;

use App\Enums\CustomerTypeEnum;
use App\Enums\QuoteTypeId;
use App\Http\Requests\TravelMemberDetailRequest;
use App\Models\QuoteMemberDetail;
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
        $travelMemberDetails = $request->validated();
        if($request->customer_type == CustomerTypeEnum::Individual) {
            if(!in_array('travel_quote_request_id', $request->validated())) {
                $travelMemberDetails = array_merge([
                    'travel_quote_request_id' => $request->quote_request_id,
                ], $travelMemberDetails);
                unset($travelMemberDetails['quote_request_id']);
            }

            $travelMemberCount = TravelMemberDetail::where('customer_id', $travelMemberDetails['customer_id'])->count();
            TravelMemberDetail::create(array_merge($travelMemberDetails, [
                'code' => CustomerTypeEnum::IndividualShort .'-'. $travelMemberDetails['customer_id'] .'-'. ++$travelMemberCount
            ]));

            TravelQuote::find($travelMemberDetails['travel_quote_request_id'])->update(['quote_updated_at' => Carbon::now()]);
        } else {

            $customerEntityId = $request->entity_id;
            $travelMemberDetails = array_merge($travelMemberDetails, [
                'customer_entity_id' => $customerEntityId,
                'customer_type' => CustomerTypeEnum::Entity
            ]);
            unset($travelMemberDetails['customer_id']);

            $quoteMemberCount = QuoteMemberDetail::where([
                'customer_type' => $request->customer_type,
                'customer_entity_id' => $customerEntityId,
                'quote_type_id' => QuoteTypeId::Travel,
            ])->count();

            QuoteMemberDetail::updateOrCreate(array_merge($travelMemberDetails), [
                'quote_type_id' => QuoteTypeId::Travel,
                'code' => CustomerTypeEnum::EntityShort . '-' . $request->entity_id . '-' .(++$quoteMemberCount),
            ]);
        }

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
        $travelMemberDetails = $request->validated();
        if($request->customer_type == CustomerTypeEnum::Individual) {
            if(!in_array('travel_quote_request_id', $request->validated())) {
                $travelMemberDetails = array_merge([
                    'travel_quote_request_id' => $request->quote_request_id
                ], $travelMemberDetails);
                unset($travelMemberDetails['quote_request_id']);
            }

            TravelMemberDetail::findOrFail($id)->update($travelMemberDetails);
            $travelMemberData = $request->only(['dob', 'nationality_id']);

            TravelQuote::where('primary_member_id', $id)->update($travelMemberData);
            TravelQuote::find($travelMemberDetails['travel_quote_request_id'])->update(['quote_updated_at' => Carbon::now()]);
        } else {
            $customerEntityId = $request->entity_id;
            $travelMemberDetails = array_merge($travelMemberDetails, [
                'customer_entity_id' => $customerEntityId,
                'customer_type' => CustomerTypeEnum::Entity
            ]);

            QuoteMemberDetail::findOrFail($id)->update(array_merge($travelMemberDetails, ['quote_type_id' => QuoteTypeId::Travel]));
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
        $data = TravelMemberDetail::find($id);
        if ($data) {
            TravelQuote::find($data->travel_quote_request_id)->update(['quote_updated_at' => Carbon::now(), 'primary_member_id' => null]);
            $data->delete();
        }

        return redirect()->back();
    }
}
