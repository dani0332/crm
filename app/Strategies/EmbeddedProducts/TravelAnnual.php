<?php

namespace App\Strategies\EmbeddedProducts;

use App\Models\TravelQuote;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TravelAnnual extends EmbeddedProduct
{
    public function getExcelColumns()
    {
        return [
            'EP REF-ID',
            'ADVISOR NAME',
            'PAYMENT DATE',
            'PLAN COMMENCEMENT DATE',
            'PLAN END DATE',
            'PASSPORT NUMBER',
            'FULL NAME',
            'EMIRATES ID NUMBER',
            'DOB',
            'AGE',
            'NATIONALITY',
            'CONTRIBUTION AMOUNT',
            'POLICY ISSUE STATUS',
        ];
    }

    public function getExcelData($certificate)
    {
        return [
            $certificate->ref_id,
            $certificate->advisor_name,
            $certificate->payment_date,
            $certificate->plan_start_date,
            $certificate->plan_end_date,
            $certificate->passport_number,
            $certificate->name,
            $certificate->emirates_id_number,
            $certificate->dob,
            $certificate->age,
            $certificate->nationality,
            $certificate->contribution_amount,
            $certificate->status,
        ];
    }

    /**
     * Retrieves sold transaction data from a dataset.
     *
     * @return Collection
     */
    public function getTransactionData($dataset, $isAlfredProtect = false)
    {
        $dataset->each(function ($item) {
            $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
            $quoteObject = $item->quoteRequest;
            $quoteObject = TravelQuote::where('uuid', $quoteObject->code)->with('customer')->first();
            $status = $quoteObject->quoteStatus->text ?? '';
            $customer = $quoteObject->customer ?? null;
            $advisorName = $quoteObject->advisor->name ?? '';
            $nationality = $quoteObject->customer->nationality->text ?? '';

            $age = isset($quoteObject->dob) ?
                Carbon::parse($quoteObject->dob)->diffInYears(Carbon::now()).' Years'
                : '';
            $planStartDate = (! empty($quoteObject->policy_start_date) && $quoteObject->policy_start_date != '0000-00-00 00:00:00') ? Carbon::parse($quoteObject->policy_start_date)->format($dateFormat) : '';
            $planEndDate = '';
            if (! empty($planStartDate)) {
                $planEndDate = Carbon::parse($quoteObject->policy_start_date)->addYear()->format($dateFormat);
            }

            if (! empty($quoteObject->quoteRequestEntityMapping)) {
                $firstName = $quoteObject->first_name ?? '';
                $lastName = $quoteObject->last_name ?? '';
            } else {
                $firstName = $customer->insured_first_name ?? '';
                $lastName = $customer->insured_last_name ?? '';
            }

            $item->id = $item->id;
            $item->ref_id = $item->code;
            $item->advisor_name = $advisorName;
            $item->payment_date = isset($item->paid_at) ? Carbon::parse($item->paid_at)->format($dateFormat) : '';
            $item->plan_start_date = $planStartDate;
            $item->plan_end_date = $planEndDate;
            $item->name = $firstName.' '.$lastName;
            $item->dob = isset($quoteObject->dob) ? Carbon::parse($quoteObject->dob)->format($dateFormat) : '';
            $item->age = $age;
            $item->contact_number = $quoteObject->mobile_no ?? '';
            $item->nationality = $nationality ?? '';
            $item->email = $quoteObject->email ?? '';
            $item->contribution_amount = 'AED '.$item->price_with_vat.'/-';
            $item->status = $status;
            $item->policy_issuance_date = $quoteObject->policy_issuance_date ?? '';
            $item->emirates_id_number = $customer->emirates_id_number ?? '';

            return $item;
        });

        return $dataset;
    }
}
