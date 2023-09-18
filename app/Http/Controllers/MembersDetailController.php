<?php

namespace App\Http\Controllers;

use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Http\Requests\MemberDetailRequest;
use App\Models\HealthMemberDetail;
use App\Models\HealthQuote;
use App\Models\QuoteMemberDetail;
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
        if (strtolower($request->quote_type) == strtolower(quoteTypeCode::Health)) {
            HealthMemberDetail::create($request->validated());
            HealthQuote::find($request->health_quote_request_id)->update(['quote_updated_at' => Carbon::now()]);
        } else {
            $quoteObject = $this->getQuoteObject(strtolower($request->quote_type), $request->quote_request_id);

            if($quoteObject) {
                $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($request->quote_type));
                QuoteMemberDetail::updateOrCreate(array_merge($request->validated(), ['quote_type_id' => $quoteTypeId]));
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
        if (strtolower($request->quote_type) == strtolower(quoteTypeCode::Health)) {
            HealthMemberDetail::findOrFail($id)->update($request->validated());

            $healthMemberData = $request->only(['gender', 'dob', 'nationality_id', 'emirate_of_your_visa_id', 'member_category_id', 'salary_band_id']);
            HealthQuote::where('primary_member_id', $id)->update($healthMemberData);

            $heathLeadData = ['quote_updated_at' => Carbon::now()];
            if ($request->update_lead_against_member) {
                $heathLeadData = array_merge($heathLeadData, $healthMemberData);
            }

            HealthQuote::find($request->health_quote_request_id)->update($heathLeadData);
        } else {
            $quoteObject = $this->getQuoteObject(strtolower($request->quote_type), $request->quote_request_id);
            $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($request->quote_type));
            QuoteMemberDetail::findOrFail($id)->update(array_merge($request->validated(), ['quote_type_id' => $quoteTypeId]));
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

        if (strtolower($explode[0]) == strtolower(quoteTypeCode::Health)) {
            $data = HealthMemberDetail::find($explode[1]);
            if ($data) {
                HealthQuote::find($data->health_quote_request_id)->update(['quote_updated_at' => Carbon::now(), 'primary_member_id' => null]);
                $data->delete();
            }
        } else {
            $memberDetails = QuoteMemberDetail::findOrFail($explode[1]);
            $memberDetails->delete();

            $quoteObject = $this->getQuoteObject(strtolower($explode[0]), $memberDetails->quote_request_id);
            $quoteObject->updated_at = Carbon::now();
            $quoteObject->save();

        }

        return redirect()->back();
    }
}
