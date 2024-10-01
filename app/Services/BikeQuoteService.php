<?php

namespace App\Services;

use App\Enums\CarPlanType;
use App\Enums\PaymentStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Models\CarPlan;
use App\Models\Payment;
use App\Models\PersonalQuote;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PDF;

class BikeQuoteService extends BaseService
{
    use GenericQueriesAllLobs;

    protected $httpService;

    public function __construct(HttpRequestService $httpService)
    {
        $this->httpService = $httpService;
    }

    public function getPlans($id, $isRenewalSort = false, $isDisabledEnabled = false)
    {
        $quotePlans = app(CentralService::class)->getQuotePlans(quoteTypeCode::Bike, $id, $isRenewalSort, false, $isDisabledEnabled);

        if (isset($quotePlans->message) && $quotePlans->message != '') {
            $listQuotePlans = $quotePlans->message;
        } else {
            if (gettype($quotePlans) != 'string' && isset($quotePlans->quotes->plans)) {
                $listQuotePlans = $quotePlans->quotes->plans;
            } elseif (! isset($quotePlans->quotes->plans)) {
                $listQuotePlans = 'Plans not available!';
            } else {
                $listQuotePlans = $quotePlans;
            }
        }

        return $listQuotePlans;
    }

    public function updateManualPlansBulk($request)
    {
        $apiEndPoint = config('constants.KEN_API_ENDPOINT').'/save-manual-bike-quote-plan';
        $apiToken = config('constants.KEN_API_TOKEN');
        $apiTimeout = config('constants.KEN_API_TIMEOUT');
        $apiUserName = config('constants.KEN_API_USER');
        $apiPassword = config('constants.KEN_API_PWD');
        if ($request->planIds) {
            $data = $request->planIds;
            $isDisabled = $request->toggle;
            $plansArray = [];
            for ($i = 0; $i < count($data); $i++) {
                $apiArray = [
                    'planId' => (int) $data[$i],
                    'isDisabled' => filter_var($isDisabled, FILTER_VALIDATE_BOOLEAN),
                ];
                array_push($plansArray, $apiArray);
            }

            $dataArray = [
                'quoteUID' => $request->bike_quote_uuid,
                'update' => true,
                'plans' => $plansArray,
            ];
            $apiCreds = [
                'apiEndPoint' => $apiEndPoint,
                'apiToken' => $apiToken,
                'apiTimeout' => $apiTimeout,
                'apiUserName' => $apiUserName,
                'apiPassword' => $apiPassword,
            ];

            $response = $this->httpService->processRequest($dataArray, $apiCreds);

            return $response;
        }
    }

    public function exportPlansPdf($quoteType, $data, $quotePlans = null)
    {
        $planIds = $data['plan_ids'];
        $addons = (isset($data['addons'])) ? $data['addons'] : null;

        if ($quotePlans == null) {
            $quotePlans = app(CentralService::class)->getQuotePlans(quoteTypeCode::Bike, $data['quote_uuid']);
        }

        if (! isset($quotePlans->quotes->plans)) {
            return ['error' => 'Quote plans not available'];
        }

        $quote = $this->getQuoteObjectBy($quoteType, $data['quote_uuid'], 'uuid');

        $quote->load(['bikeQuote.bikeMake', 'bikeQuote.bikeModel', 'advisor' => function ($q) {
            $q->select('id', 'email', 'mobile_no', 'name', 'landline_no');
        }, 'customer']);

        $pdf = PDF::setOption(['isHtml5ParserEnabled' => true, 'dpi' => 150])->loadView('pdf.bike_quote_plans', compact('quotePlans', 'planIds', 'quote', 'addons'));

        // generate pdf with file name e.g. InsuranceMarket.ae™ Motor Insurance Comparison for Rahul.pdf
        $pdfName = 'InsuranceMarket.ae™ Motor Insurance Comparison for '.$quote->first_name.' '.$quote->last_name.'.pdf';

        return ['pdf' => $pdf, 'name' => $pdfName];
    }

    public function getNonQuotedBikePlans($insuranceProviderId, $quotePlanId)
    {
        return CarPlan::select([
            'id',
            'text',
            'repair_type',
            DB::raw('IF(repair_type = "'.CarPlanType::COMP.'", CONCAT(text, " (NON-AGENCY)"), CONCAT(text, " (", repair_type, ")")) as plan_name'),
        ])
            ->where('provider_id', $insuranceProviderId)
            ->whereNotIn('id', $quotePlanId)
            ->where('quote_type_id', QuoteTypeId::Bike)
            ->get();
    }

    public function bikePlanModify($request)
    {
        if (($response = $this->isPlanModifyAllowed($request->all())) === true) {
            $apiEndPoint = config('constants.KEN_API_ENDPOINT').'/save-manual-bike-quote-plan';
            $apiToken = config('constants.KEN_API_TOKEN');
            $apiTimeout = config('constants.KEN_API_TIMEOUT');
            $apiUserName = config('constants.KEN_API_USER');
            $apiPassword = config('constants.KEN_API_PWD');

            if (isset($request->is_create)) {
                if ($request->is_create == 1) {
                    $discountedPremium = $request->actual_premium;
                    $isUpdate = false;
                } else {
                    $discountedPremium = $request->discounted_premium;
                    $isUpdate = true;
                }
            } else {
                $discountedPremium = $request->actual_premium;
            }

            $addons = [];
            if ($request->addons != null && count($request->addons) > 0) {
                $addons = $request->addons;
            } else {
                $addons = [];
            }

            $carPlanData = [
                'quoteUID' => $request->bike_quote_uuid,
                'update' => $isUpdate,
                'url' => strval($request->current_url),
                'ipAddress' => request()->ip(),
                'userAgent' => request()->header('User-Agent'),
                'userId' => strval(auth()->id()),
                'plans' => [
                    [
                        'planId' => (int) $request->bike_plan_id,
                        'actualPremium' => (float) $request->actual_premium,
                        'bikeValue' => (float) $request->bike_value,
                        'excess' => (float) $request->excess,
                        'discountPremium' => (float) $discountedPremium,
                        'isDisabled' => isset($request->is_disabled) ? (bool) $request->is_disabled : (bool) false,
                        'addons' => $addons,
                        'insurerTrimId' => strval($request->insurerTrim),
                        'insurerQuoteNo' => strval($request->insurer_quote_no),
                        'isManualUpdate' => $request->is_manual_update,
                        'ancillaryExcess' => (int) $request->ancillary_excess,
                    ],
                ],
            ];

            $apiCreds = [
                'apiEndPoint' => $apiEndPoint,
                'apiToken' => $apiToken,
                'apiTimeout' => $apiTimeout,
                'apiUserName' => $apiUserName,
                'apiPassword' => $apiPassword,
            ];

            $response = $this->httpService->processRequest($carPlanData, $apiCreds);
        }

        return $response;
    }

    /**
     * @return bool|string
     *                     paid_at = authorized date
     */
    public function isPlanModifyAllowed($data)
    {
        $logPrefix = 'fn: isPlanModifyAllowed ';
        $quote = PersonalQuote::where('uuid', $data['bike_quote_uuid'])->with('paymentStatus')->first();

        if (in_array($quote->payment_status_id, [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED])) {
            $bikePayment = Payment::where('code', '=', $quote->code)->first();
            if (! empty($bikePayment->captured_at)) {
                $paymentCapturedAt = $bikePayment->captured_at;
                $today = Carbon::today();

                $dateLimitForAdvisor = Carbon::parse($paymentCapturedAt)->addDays(6);
                $dateLimitForManager = Carbon::parse($dateLimitForAdvisor)->addDays(6);

                if (Auth::user()->hasRole(RolesEnum::BikeAdvisor) && $today->lte($dateLimitForAdvisor)) {
                    info($logPrefix.' plan modify allowed to advisor for uuid '.$quote->uuid.' and captured days diff is '.$paymentCapturedAt);

                    return true;
                } elseif (Auth::user()->hasRole(RolesEnum::BikeManager) && $today->gt($dateLimitForAdvisor) && $today->lte($dateLimitForManager)) {
                    info($logPrefix.' plan modify allowed to bike manager for uuid '.$quote->uuid.' and captured days diff is '.$paymentCapturedAt);

                    return true;
                }
            }
        }

        if (in_array($quote->payment_status_id, [PaymentStatusEnum::CANCELLED, PaymentStatusEnum::REFUNDED]) && Auth::user()->hasAnyRole([RolesEnum::BikeAdvisor, RolesEnum::BikeManager])) {
            info($logPrefix.' plan modify allowed to advisor for uuid '.$quote->uuid);

            return true;
        }

        if (
            $quote->payment_status_id == '' || $quote->payment_status_id == null || (in_array($quote->payment_status_id, [PaymentStatusEnum::AUTHORISED, PaymentStatusEnum::PENDING, PaymentStatusEnum::FAILED, PaymentStatusEnum::DECLINED, PaymentStatusEnum::DRAFT])
                && Auth::user()->hasAnyRole([RolesEnum::BikeAdvisor,  RolesEnum::BikeManager]))
        ) {
            info($logPrefix.' plan modify allowed for uuid '.$quote->uuid);

            return true;
        }

        info($logPrefix.' plan modification is not allowed for uuid '.$quote->uuid);

        return 'Plan Modification is not allowed';
    }
}
