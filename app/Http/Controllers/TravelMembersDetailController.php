<?php

namespace App\Http\Controllers;

use App\Enums\CustomerTypeEnum;
use App\Enums\QuoteTypes;
use App\Http\Requests\TravelMemberDetailRequest;
use App\Models\CustomerMembers;
use App\Models\TravelMemberDetail;
use App\Models\TravelQuote;
use App\Services\TravelQuoteService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;

class TravelMembersDetailController extends Controller
{
    use GenericQueriesAllLobs;
    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(TravelMemberDetailRequest $request)
    {
        $quoteMemberDetails = $request->validated();
        $quoteObject = $this->getQuoteObject(strtolower($request->quote_type), $request->travel_quote_request_id);
        if ($quoteObject) {
            $quoteMemberDetails = $this->fillFirstAndLastName($quoteMemberDetails, request()->quote_type);

            $quoteModel = $this->getModelObject(strtolower($request->quote_type));

            if ($request->customer_type == CustomerTypeEnum::Individual) {
                if (! in_array('travel_quote_request_id', $request->validated())) {
                    $quoteMemberDetails = array_merge([
                        'travel_quote_request_id' => $request->quote_request_id,
                    ], $quoteMemberDetails);
                }

                $customerEntityId = $request->customer_id;
                $quoteMemberDetails = array_merge($quoteMemberDetails, [
                    'customer_entity_id' => $customerEntityId,
                    'customer_type' => CustomerTypeEnum::Individual,
                    'quote_id' => $request->travel_quote_request_id ?? '',
                ]);

            } else {
                $customerEntityId = $request->entity_id;
                $quoteMemberDetails = array_merge($quoteMemberDetails, [
                    'customer_entity_id' => $customerEntityId,
                    'customer_type' => CustomerTypeEnum::Entity,
                    'quote_id' => $request->travel_quote_request_id ?? '',
                    'gender' => $request->gender ?? '',
                ]);
            }

            unset($quoteMemberDetails['name']);
            unset($quoteMemberDetails['customer_id']);
            unset($quoteMemberDetails['travel_quote_request_id']);

            $quoteMemberDetails = CustomerMembers::updateOrCreate(array_merge($quoteMemberDetails), [
                'quote_type' => ltrim($quoteModel, "'\'"),
                'code' => generateQuoteMemberCode($request->customer_type, $customerEntityId),
                'is_payer' => isset($request->is_payer) && $request->is_payer == 1,
                'is_third_party_payer' => $request->is_third_party_payer ?? false,
            ]);

            $quoteMemberDetails = $quoteMemberDetails->load(['relation', 'nationality']);
            $quoteObject->updated_at = Carbon::now();
            $quoteObject->save();

            app(TravelQuoteService::class)->setQuoteUpdatedAt($quoteObject->id);

            return redirect()->back(302, ['status' => true, 'message' => 'Updated', 'data' => $quoteMemberDetails]);

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
        $quoteMemberDetails = $request->validated();
        $quoteObject = $this->getQuoteObject(strtolower($request->quote_type), $request->travel_quote_request_id);

        if ($quoteObject) {
            $quoteModel = $this->getModelObject(strtolower($request->quote_type));

            $quoteMemberDetails = $this->fillFirstAndLastName($quoteMemberDetails, request()->quote_type);

            if ($request->customer_type == CustomerTypeEnum::Individual) {
                if (! in_array('travel_quote_request_id', $request->validated())) {
                    $quoteMemberDetails = array_merge([
                        'travel_quote_request_id' => $request->quote_request_id,
                    ], $quoteMemberDetails);
                }

                $customerEntityId = $request->customer_id;
                $quoteMemberDetails = array_merge($quoteMemberDetails, [
                    'customer_entity_id' => $customerEntityId,
                    'customer_type' => CustomerTypeEnum::Individual,
                ]);
            } else {
                $customerEntityId = $request->entity_id;
                $quoteMemberDetails = array_merge($quoteMemberDetails, [
                    'customer_entity_id' => $customerEntityId,
                    'customer_type' => CustomerTypeEnum::Entity,
                ]);
            }

            $memberDetail = CustomerMembers::findOrFail($id);
            $memberDetail->update(array_merge($quoteMemberDetails,
                [
                    'quote_type' => ltrim($quoteModel, "'\'"),
                    'is_payer' => isset($request->is_payer) && $request->is_payer == 1,
                ]));

            $travelMemberData = $request->only(['dob', 'nationality_id', 'gender']);

            TravelQuote::where('primary_member_id', $id)->update($travelMemberData);

            $quoteObject->updated_at = Carbon::now();
            $quoteObject->save();

            $memberDetail = $memberDetail->load(['relation', 'nationality']);

            app(TravelQuoteService::class)->setQuoteUpdatedAt($quoteObject->id);

            return redirect()->back(302, ['status' => true, 'message' => 'Updated', 'data' => $memberDetail]);
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
        $data = CustomerMembers::find($id);
        if ($data) {
            TravelQuote::find($data->quote_id)->update(['primary_member_id' => null]);
            $data->delete();
        }

        return redirect()->back();
    }

    /*
     * // break first name and fill first name and last name
     * */
    private function fillFirstAndLastName($quoteMemberDetails, $quoteType)
    {
        if (ucwords($quoteType) == QuoteTypes::TRAVEL->value) {
            $name = explode(' ', $quoteMemberDetails['name'], 2);
            $quoteMemberDetails['first_name'] = $name[0];
            $quoteMemberDetails['last_name'] = $name[1];
        }

        return $quoteMemberDetails;
    }
}
