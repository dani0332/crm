<?php

namespace App\Http\Controllers;

use App\Enums\CustomerTypeEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Http\Requests\MemberDetailRequest;
use App\Models\HealthMemberDetail;
use App\Models\HealthQuote;
use App\Models\QuoteMemberDetail;
use App\Models\TravelMemberDetail;
use App\Models\TravelQuote;
use App\Services\LookupService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;

class MembersDetailController extends Controller
{
    use GenericQueriesAllLobs;

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(MemberDetailRequest $request)
    {
        if (strtolower($request->quote_type) == strtolower(quoteTypeCode::Health) &&
            (isset($request->customer_type) && $request->customer_type == CustomerTypeEnum::Individual)) {
            $healthMemberDetails = $request->validated();

            if(!in_array('health_quote_request_id', $request->validated())) {
                $healthMemberDetails = array_merge([
                    'health_quote_request_id' => $request->quote_request_id,
                ], $healthMemberDetails);
                unset($healthMemberDetails['quote_request_id']);
            }

            $healthMemberCount = HealthMemberDetail::where('customer_id', $healthMemberDetails['customer_id'])->count();
            HealthMemberDetail::create(array_merge($healthMemberDetails, [
                'code' => CustomerTypeEnum::IndividualShort .'-'. $healthMemberDetails['customer_id'] .'-'. ++$healthMemberCount
            ]));

            HealthQuote::find($healthMemberDetails['health_quote_request_id'])->update(['quote_updated_at' => Carbon::now()]);

        } elseif(strtolower($request->quote_type) == strtolower(quoteTypeCode::Travel) &&
            (isset($request->customer_type) && $request->customer_type == CustomerTypeEnum::Individual)) {
            $travelMemberDetails = $request->validated();

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
            $quoteMemberDetails = $request->validated();
            $quoteObject = $this->getQuoteObject(strtolower($request->quote_type), $request->quote_request_id);

            if($quoteObject) {
                $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($request->quote_type));

                if($request->customer_type == CustomerTypeEnum::Individual) {
                    $customerEntityId = $request->customer_id;
                    $quoteMemberDetails = array_merge($quoteMemberDetails, [
                        'customer_entity_id' => $customerEntityId,
                        'customer_type' => CustomerTypeEnum::Individual
                    ]);
                } else {
                    $customerEntityId = $request->entity_id;
                    $quoteMemberDetails = array_merge($quoteMemberDetails, [
                        'customer_entity_id' => $customerEntityId,
                        'customer_type' => CustomerTypeEnum::Entity
                    ]);
                }
                unset($quoteMemberDetails['customer_id']);

                $quoteMemberCount = QuoteMemberDetail::where([
                    'customer_type' => $request->customer_type,
                    'customer_entity_id' => $customerEntityId,
                    'quote_type_id' => $quoteTypeId,
                ])->count();

                $quoteMemberCode = ($request->customer_type == CustomerTypeEnum::Individual) ?
                    CustomerTypeEnum::IndividualShort . '-' . $request->customer_id . '-' .(++$quoteMemberCount) :
                    CustomerTypeEnum::EntityShort . '-' . $request->entity_id . '-' .(++$quoteMemberCount);

                QuoteMemberDetail::updateOrCreate(array_merge($quoteMemberDetails), [
                    'quote_type_id' => $quoteTypeId,
                    'code' => $quoteMemberCode,
                ]);
                $quoteObject->updated_at = Carbon::now();
                $quoteObject->save();
            }
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(MemberDetailRequest $request, $id)
    {
        if (strtolower($request->quote_type) == strtolower(quoteTypeCode::Health) &&
            (isset($request->customer_type) && $request->customer_type == CustomerTypeEnum::Individual)) {
            $healthMemberDetails = $request->validated();

            if(!in_array('health_quote_request_id', $request->validated())) {
                $healthMemberDetails = array_merge([
                    'health_quote_request_id' => $request->quote_request_id
                ], $healthMemberDetails);
                unset($healthMemberDetails['quote_request_id']);
            }

            HealthMemberDetail::findOrFail($id)->update($healthMemberDetails);

            $healthMemberData = $request->only(['gender', 'dob', 'nationality_id', 'emirate_of_your_visa_id', 'member_category_id', 'salary_band_id']);
            HealthQuote::where('primary_member_id', $id)->update($healthMemberData);

            $heathLeadData = ['quote_updated_at' => Carbon::now()];
            if ($request->update_lead_against_member) {
                $heathLeadData = array_merge($heathLeadData, $healthMemberData);
            }

            HealthQuote::find($healthMemberDetails['health_quote_request_id'])->update($heathLeadData);

        } elseif(strtolower($request->quote_type) == strtolower(quoteTypeCode::Travel) &&
            (isset($request->customer_type) && $request->customer_type == CustomerTypeEnum::Individual)) {
            $travelMemberDetails = $request->validated();

            if(!in_array('travel_quote_request_id', $request->validated())) {
                $travelMemberDetails = array_merge([
                    'travel_quote_request_id' => $request->quote_request_id,
                ], $travelMemberDetails);
                unset($travelMemberDetails['quote_request_id']);
            }

            TravelMemberDetail::findOrFail($id)->update($travelMemberDetails);
            TravelQuote::find($travelMemberDetails['travel_quote_request_id'])->update(['quote_updated_at' => Carbon::now()]);
            TravelQuote::where('primary_member_id', $id)->update(['dob' => $travelMemberDetails['dob']]);

        } else {
            $quoteMemberDetails = $request->validated();
            $quoteObject = $this->getQuoteObject(strtolower($request->quote_type), $request->quote_request_id);
            $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($request->quote_type));

            if ($request->customer_type == CustomerTypeEnum::Individual) {
                $customerEntityId = $request->customer_id;
                $quoteMemberDetails = array_merge($quoteMemberDetails, [
                    'customer_entity_id' => $customerEntityId,
                    'customer_type' => CustomerTypeEnum::Individual
                ]);
            } else {
                $customerEntityId = $request->entity_id;
                $quoteMemberDetails = array_merge($quoteMemberDetails, [
                    'customer_entity_id' => $customerEntityId,
                    'customer_type' => CustomerTypeEnum::Entity
                ]);
            }

            QuoteMemberDetail::findOrFail($id)->update(array_merge($quoteMemberDetails, ['quote_type_id' => $quoteTypeId]));
            $quoteObject->updated_at = Carbon::now();
            $quoteObject->save();

        }

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $explode = explode('-', $id);

        if (strtolower($explode[1]) == strtolower(quoteTypeCode::Health) && $explode[0] == CustomerTypeEnum::Individual) {
            $data = HealthMemberDetail::find($explode[2]);
            if ($data) {
                HealthQuote::find($data->health_quote_request_id)->update(['quote_updated_at' => Carbon::now(), 'primary_member_id' => null]);
                $data->delete();
            }
        } else {
            $memberDetails = QuoteMemberDetail::findOrFail($explode[2]);
            $memberDetails->delete();

            $quoteObject = $this->getQuoteObject(strtolower($explode[1]), $memberDetails->quote_request_id);
            $quoteObject->updated_at = Carbon::now();
            $quoteObject->save();

        }

        return redirect()->back();
    }
}
