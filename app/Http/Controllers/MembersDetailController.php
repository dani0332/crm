<?php

namespace App\Http\Controllers;

use App\Enums\CustomerTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Http\Requests\MemberDetailRequest;
use App\Models\BusinessQuote;
use App\Models\CustomerMembers;
use App\Models\HealthMemberDetail;
use App\Models\HealthQuote;
use App\Services\CentralService;
use App\Services\LookupService;
use App\Services\TravelQuoteService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MembersDetailController extends Controller
{
    use GenericQueriesAllLobs;

    /**
     * Flash message when member/UBO changes are blocked (e.g. policy booked).
     */
    public const FLASH_ERROR_MEMBER_DETAILS_LOCKED = 'This lead is now locked as the policy has been booked. If changes are needed such midterm deletion of member or marital status change, go to \'Send Update\', select \'Add Update\', and choose \'Endorsement Financial\'';

    private function responseIfBusinessQuoteMemberDetailsLocked(Request $request, $quoteObject, bool $isDelete = false): RedirectResponse|JsonResponse|null
    {
        if (! $quoteObject instanceof BusinessQuote) {
            return null;
        }

        if ($request->boolean('from_aml_model') && $isDelete == false) {
            return null;
        }

        $lockLeadSectionsDetails = app(CentralService::class)->lockLeadSectionsDetails($quoteObject);

        if (isset($lockLeadSectionsDetails['member_details']) && $lockLeadSectionsDetails['member_details'] === true) {
            if ($this->memberLockResponseShouldBeJson($request)) {
                return response()->json([
                    'status' => false,
                    'message' => self::FLASH_ERROR_MEMBER_DETAILS_LOCKED,
                ], 403);
            }

            return redirect()->back()->with('error', self::FLASH_ERROR_MEMBER_DETAILS_LOCKED);
        }

        return null;
    }

    /**
     * Non-Inertia clients that expect JSON get a 403 JSON body. Inertia visits send X-Inertia and must receive a redirect with flash.
     */
    private function memberLockResponseShouldBeJson(Request $request): bool
    {
        if ($request->headers->has('X-Inertia')) {
            return false;
        }

        return $request->expectsJson();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function store(MemberDetailRequest $request)
    {
        $quoteMemberDetails = $request->validated();
        $quoteObject = $this->getQuoteObject(strtolower($request->quote_type), $request->quote_request_id);
        if ($quoteObject) {
            if ($response = $this->responseIfBusinessQuoteMemberDetailsLocked($request, $quoteObject)) {
                return $response;
            }

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
            unset($quoteMemberDetails['pec']);

            if (empty($quoteMemberDetails['first_name']) && empty($quoteMemberDetails['last_name'])) {
                $quoteMemberCount = CustomerMembers::where([
                    'customer_type' => $request->customer_type,
                    'customer_entity_id' => $customerEntityId,
                    'first_name' => GenericRequestEnum::MEMBER,
                ])->count();

                $quoteMemberDetails['first_name'] = GenericRequestEnum::MEMBER;
                $quoteMemberDetails['last_name'] = (++$quoteMemberCount);
            }

            $isThirdPartyPayer = $request->is_third_party_payer ?? false;
            $isInsured = $isThirdPartyPayer ? false : ($request->is_insured ?? true);

            $quoteMemberDetails = CustomerMembers::updateOrCreate(array_merge($quoteMemberDetails), [
                'quote_type' => ltrim($quoteModel, "'\'"),
                'code' => generateQuoteMemberCode($request->customer_type, $customerEntityId),
                'is_payer' => isset($request->is_payer) && $request->is_payer == 1,
                'is_third_party_payer' => $isThirdPartyPayer,
                'is_insured' => $isInsured,
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
     * @return Response
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
     * @param  Request  $request
     * @param  int  $id
     * @return RedirectResponse
     */
    public function update(MemberDetailRequest $request, $id)
    {
        $quoteMemberDetails = $request->validated();
        $quoteObject = $this->getQuoteObject(strtolower($request->quote_type), $request->quote_request_id);

        if ($quoteObject) {
            if ($response = $this->responseIfBusinessQuoteMemberDetailsLocked($request, $quoteObject)) {
                return $response;
            }

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

            unset($quoteMemberDetails['pec']);

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
            app(TravelQuoteService::class)->updateCustomerProfileDetails($request->quote_type, $quoteObject->uuid);

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
            if ($response = $this->responseIfBusinessQuoteMemberDetailsLocked($request, $quoteObject)) {
                return $response;
            }

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

            unset($quoteMemberDetails['pec']);

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
     * @return RedirectResponse
     */
    public function destroy($id)
    {
        $explode = explode('-', $id);
        $memberDetails = CustomerMembers::findOrFail($explode[2] ?? '');

        $quoteObject = $this->getQuoteObject(strtolower($explode[1] ?? ''), $memberDetails->quote_id);

        if ($quoteObject && ($response = $this->responseIfBusinessQuoteMemberDetailsLocked(request(), $quoteObject, true))) {
            return $response;
        }

        if (strtolower($explode[1] ?? '') == strtolower(quoteTypeCode::Health) && ($explode[0] ?? '') == CustomerTypeEnum::Individual) {
            HealthQuote::find($memberDetails->quote_id)->update(['quote_updated_at' => Carbon::now(), 'primary_member_id' => null]);
        }

        $quoteObject->updated_at = Carbon::now();
        $quoteObject->save();

        $memberDetails->delete();

        return redirect()->back();

    }
}
