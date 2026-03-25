<?php

namespace App\Strategies\EmbeddedProducts;

use App\Enums\CarPlanType;
use App\Enums\CarVehicleUse;
use App\Enums\EpEcbExcludeVehicleEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\EmbeddedTransaction;
use Carbon\Carbon;

class ECB extends EmbeddedProduct
{
    public function getExcelColumns()
    {
        return [
            'EP REF-ID',
            'DATE OF ISSUANCE', // Payment Date
            'PLAN COMMENCEMENT DATE', // Plan Start Date
            'PLAN END DATE',
            'FULL NAME',
            'EMIRATES ID NUMBER',
            'Contribution Amount', // Payment Amount With VAT
            'POLICY ISSUE STATUS',
            'EP Payment Status',
            'EP API Status',
            'EP Sage Status',
            'Certificate Number',
            'Tax Invoice Number',
            'Tax Invoice Raised by Buyer Number',
            'Model Year',
            'Make',
            'Model',
            'Chassis Number',
            'Excess Amount',
        ];
    }

    public function getExcelData($certificate)
    {
        return [
            $certificate->ref_id,
            $certificate->payment_date,
            $certificate->plan_start_date,
            $certificate->plan_end_date,
            $certificate->name,
            $certificate->emirates_id_number,
            $certificate->contribution_amount,
            $certificate->status,
            $certificate->ep_payment_status,
            $certificate->ep_api_status,
            $certificate->ep_sage_status,
            $certificate->certificate_number,
            $certificate->tax_invoice_no ?? '',
            $certificate->tax_invoice_buyer_no ?? '',
            $certificate->model_year,
            $certificate->make,
            $certificate->model,
            $certificate->chassis_number,
            $certificate->excess_amount,
        ];
    }

    protected function getReportRelations()
    {
        return [
            'product.embeddedProduct',
            'quoteRequest.carMake',
            'quoteRequest.carModel',
            'quoteRequest.customer',
            'quoteRequest.latestInsured',
            'quoteRequest.quoteStatus',
            'quoteRequest.quoteRequestEntityMapping',
            'paymentStatus',
        ];
    }

    public function updateQuery($query, $filters)
    {
        return $query->join('car_quote_request_detail', function ($join) {
            $join->on('embedded_transactions.quote_request_id', '=', 'car_quote_request_detail.car_quote_request_id')
                ->where('embedded_transactions.quote_request_type', '=', 'App\\Models\\CarQuote');
        })
            ->when(isset($filters['chassis_number']), function ($query) use ($filters) {
                $query->where('car_quote_request_detail.chassis_number', 'like', "%{$filters['chassis_number']}%");
            });
    }

    public function processReportRecord($quoteObject, $item)
    {
        $item->model_year = $quoteObject?->year_of_manufacture ?? '';
        $item->make = $quoteObject?->carMake?->text ?? '';
        $item->model = $quoteObject?->carModel?->text ?? '';
        $item->chassis_number = $quoteObject?->carQuoteRequestDetail?->chassis_number ?? '';
        $item->excess_amount = $quoteObject?->carQuoteRequestDetail?->excess.'/-';
        $item->tax_invoice_no = $item->tax_invoice_no ?? '';
        $item->tax_invoice_buyer_no = $item->tax_invoice_buyer_no ?? '';

        return $item;
    }

    public function isCriteriaMatched($quote): bool
    {
        $carMakeCode = $quote->carMake?->code;
        $carModelCode = $quote->carModel?->code;
        $planRepairType = $quote->plan?->repair_type;

        if (empty($carMakeCode) || empty($carModelCode)) {
            return false;
        }

        $isNotExcludedCarMake = ! in_array($carMakeCode, EpEcbExcludeVehicleEnum::CAR_MAKE_CODES);
        $isNotExcludedCarModel = ! in_array($carModelCode, EpEcbExcludeVehicleEnum::CAR_MODEL_CODES);
        $isNotCommercialVehicle = $quote->vehicle_use !== CarVehicleUse::COMMERCIAL;
        $isNotModifiedVehicle = ! $quote->is_modified;
        $isNotTPLPlan = $planRepairType !== CarPlanType::TPL;

        return $isNotExcludedCarMake
            && $isNotExcludedCarModel
            && $isNotCommercialVehicle
            && $isNotModifiedVehicle
            && $isNotTPLPlan;
    }

    public function isDisabled(EmbeddedTransaction $epTransaction): bool
    {
        if ($this->preCheckEpTransactionIsDisabled($epTransaction)) {
            return true;
        }

        $quote = $epTransaction->quoteRequest;
        if ($quote === null) {
            return true;
        }

        $isCriteriaUnmatched = ! $this->isCriteriaMatched($quote);
        $isPolicyBookedDateValid = $quote->quote_status_id == QuoteStatusEnum::PolicyBooked
            ? isValidDate($quote->policy_booking_date) && Carbon::parse($quote->policy_booking_date)->diffInDays(Carbon::now()) <= 30
            : true;

        return $isCriteriaUnmatched || ! $isPolicyBookedDateValid;
    }
}
