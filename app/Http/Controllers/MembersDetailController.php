<?php

namespace App\Http\Controllers;

use App\Enums\CustomerTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Http\Requests\MemberDetailRequest;
use App\Models\CustomerMembers;
use App\Models\HealthMemberDetail;
use App\Models\HealthQuote;
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
        $quoteMemberDetails = $request->validated();
        $quoteObject = $this->getQuoteObject(strtolower($request->quote_type), $request->quote_request_id);
        if ($quoteObject) {
            $quoteModel = $this->getModelObject(strtolower($request->quote_type));

            if ($request->customer_type == CustomerTypeEnum::Individual) {
                $customerEntityId = $request->customer_id;
                $quoteMemberDetails = array_merge($quoteMemberDetails, [
                    'customer_entity_id' => $customerEntityId,
                    'customer_type' => CustomerTypeEnum::Individual,
                    'quote_id' => $request->quote_request_id,
                ]);
            } else {
                $customerEntityId = $request->entity_id;
                $quoteMemberDetails = array_merge($quoteMemberDetails, [
                    'customer_entity_id' => $customerEntityId,
                    'customer_type' => CustomerTypeEnum::Entity,
                    'quote_id' => $request->quote_request_id,
                ]);
            }
            unset($quoteMemberDetails['customer_id']);
            unset($quoteMemberDetails['quote_request_id']);

            if ($quoteMemberDetails['first_name'] == null && $quoteMemberDetails['last_name'] == null) {
                $quoteMemberCount = CustomerMembers::where([
                    'customer_type' => $request->customer_type,
                    'customer_entity_id' => $customerEntityId,
                    'first_name' => GenericRequestEnum::MEMBER,
                ])->count();

                $quoteMemberDetails['first_name'] = GenericRequestEnum::MEMBER;
                $quoteMemberDetails['last_name'] = (++$quoteMemberCount);
            }

            $quoteMemberDetails = CustomerMembers::updateOrCreate(array_merge($quoteMemberDetails), [
                'quote_type' => ltrim($quoteModel, "'\'"),
                'code' => generateQuoteMemberCode($request->customer_type, $customerEntityId),
                'is_payer' => isset($request->is_payer) && $request->is_payer == 1,
                'is_third_party_payer' => $request->is_third_party_payer ?? false,
            ]);

            $quoteMemberDetails = $quoteMemberDetails->load(['relation', 'nationality']);
            if (ucwords(strtolower($request->quote_type)) == QuoteTypes::HEALTH->value) {
                $quoteObject->quote_updated_at = Carbon::now();
            }
            $quoteObject->updated_at = Carbon::now();
            $quoteObject->save();

            if (isset($request->from_aml_model)) {
                return response()->json(['status' => true, 'message' => 'Updated', 'data' => $quoteMemberDetails]);
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
        $lookUpService = new LookupService;
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
        $quoteMemberDetails = $request->validated();
        $quoteObject = $this->getQuoteObject(strtolower($request->quote_type), $request->quote_request_id);

        if ($quoteObject) {
            $quoteModel = $this->getModelObject(strtolower($request->quote_type));

            if ($request->customer_type == CustomerTypeEnum::Individual) {
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

            if (ucwords(strtolower($request->quote_type)) == QuoteTypes::HEALTH->value) {
                $quoteObject->quote_updated_at = Carbon::now();
            }
            $quoteObject->updated_at = Carbon::now();
            $quoteObject->save();

            $memberDetail = $memberDetail->load(['relation', 'nationality']);

            if (isset($request->from_aml_model)) {
                return response()->json(['status' => true, 'message' => 'Updated', 'data' => $memberDetail]);
            }
        }

        return redirect()->back();
    }

    public function uboUpdate(MemberDetailRequest $request)
    {
        $quoteMemberDetails = $request->validated();
        $quoteObject = $this->getQuoteObject(strtolower($request->quote_type), $request->quote_request_id ?? $request->quote_id);

        if ($quoteObject) {
            $quoteModel = $this->getModelObject(strtolower($request->quote_type));

            if ($request->customer_type == CustomerTypeEnum::Individual) {
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

            $memberDetail = CustomerMembers::findOrFail($request->id);
            $memberDetail->update(array_merge($quoteMemberDetails,
                [
                    'quote_type' => ltrim($quoteModel, "'\'"),
                    'is_payer' => isset($request->is_payer) && $request->is_payer == 1,
                ]));

            if (ucwords(strtolower($request->quote_type)) == QuoteTypes::HEALTH->value) {
                $quoteObject->quote_updated_at = Carbon::now();
            }
            $quoteObject->updated_at = Carbon::now();
            $quoteObject->save();

            $memberDetail = $memberDetail->load(['relation', 'nationality']);

            if (isset($request->from_aml_model)) {
                return response()->json(['status' => true, 'message' => 'Updated', 'data' => $memberDetail]);
            }
        }

        return response()->json(['error' => 'Something went wrong.']);
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
        $memberDetails = CustomerMembers::findOrFail($explode[2] ?? '');

        if (strtolower($explode[1] ?? '') == strtolower(quoteTypeCode::Health) && ($explode[0] ?? '') == CustomerTypeEnum::Individual) {
            HealthQuote::find($memberDetails->quote_id)->update(['quote_updated_at' => Carbon::now(), 'primary_member_id' => null]);
        }

        $quoteObject = $this->getQuoteObject(strtolower($explode[1] ?? ''), $memberDetails->quote_id);
        $quoteObject->updated_at = Carbon::now();
        $quoteObject->save();

        $memberDetails->delete();

        return redirect()->back();

    }
}
