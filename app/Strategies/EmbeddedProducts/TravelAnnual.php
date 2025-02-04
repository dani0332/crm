<?php

namespace App\Strategies\EmbeddedProducts;

use App\Enums\PaymentStatusEnum;
use App\Models\EmbeddedTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TravelAnnual extends EmbeddedProduct
{
    public function getExcelColumns()
    {
        return [
            'EP REF-ID',
            'ADVISOR NAME',
            'DATE OF ISSUANCE',
            'PLAN COMMENCEMENT DATE',
            'PLAN END DATE',
            'FULL NAME',
            'EMIRATES ID NUMBER',
            'DOB',
            'AGE',
            'PASSPORT NUMBER',
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

    public function filterReport($ep, $filters)
    {
        $productTransaction = EmbeddedTransaction::whereHas('product.embeddedProduct', function ($query) use ($ep) {
            $query->where('id', $ep->id);
        });
        $dataset = $productTransaction->with(
            'product.embeddedProduct',
            'travelQuote',
            'travelQuote.customer',
            'travelQuote.customer.nationality',
            'travelQuote.quoteStatus',
            'travelQuote.advisor',
            'travelQuote.quoteRequestEntityMapping',
        )
            ->join('payments', function ($join) {
                $join->on('embedded_transactions.code', '=', 'payments.code')
                    ->where('payments.paymentable_type', '=', 'App\\Models\\TravelQuote');
            })
            ->where('embedded_transactions.is_selected', true)
            ->where('embedded_transactions.payment_status_id', PaymentStatusEnum::CAPTURED)
            ->when(isset($filters['ref_id']), function ($query) use ($filters) {
                $query->where('embedded_transactions.code', 'like', "%{$filters['ref_id']}%");
            })
            ->when(isset($filters['months']), function ($query) use ($filters) {
                $startDate = Carbon::parse($filters['months'])->startOfMonth()->format('Y-m-d');
                $endDate = Carbon::parse($filters['months'])->endOfMonth()->format('Y-m-d');
                $query->whereBetween('payments.captured_at', [$startDate, $endDate]);

            })
            ->when(isset($filters['name']), function ($query) use ($filters) {
                $name = $filters['name'];

                $query->whereHas('travelQuote', function ($query) use ($name) {
                    $query->where('first_name', 'like', "%{$name}%")
                        ->orWhere('last_name', 'like', "%{$name}%");
                });
            })
            ->when(isset($filters['email']), function ($query) use ($filters) {
                $query->whereHas('travelQuote', function ($query) use ($filters) {
                    $email = $filters['email'];
                    $query->where('email', 'like', "%{$email}%");
                });
            })
            ->when(isset($filters['date_of_purchase']), function ($query) use ($filters) {
                $query->whereHas('travelQuote', function ($query) use ($filters) {
                    $startDate = Carbon::parse($filters['date_of_purchase'][0])->startOfDay();
                    $endDate = Carbon::parse($filters['date_of_purchase'][1])->endOfDay();
                    $query->whereBetween('payments.captured_at', [$startDate, $endDate]);
                });
            });

        $sortBy = 'embedded_transactions.id';
        $sortOrder = 'desc';
        if (! empty($filters['sortBy']) && ! empty($filters['sortType'])) {
            $sortableColumns = [
                'payment_date' => 'payments.captured_at',
                'contribution_amount' => 'embedded_transactions.price_with_vat',
            ];
            $sortBy = $sortableColumns[$filters['sortBy']] ?? 'embedded_transactions.id';
            $sortOrder = $filters['sortType'] ?? 'desc';
        }

        $dataset = $dataset->orderBy($sortBy, $sortOrder);

        if (isset($filters['excel_export']) && $filters['excel_export'] == true) {
            $dataset = $dataset->get();
        } else {
            $dataset = $dataset->simplePaginate()->withQueryString();
        }

        return $dataset;
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
            $quoteObject = $item->travelQuote ?? $item->quoteRequest;
            $status = $quoteObject->quoteStatus->text ?? '';
            $customer = $quoteObject->customer ?? null;
            $advisorName = $quoteObject->advisor->name ?? '';
            $nationality = $quoteObject->customer->nationality->text ?? '';

            $age = isset($quoteObject->dob) ?
                floor(Carbon::parse($quoteObject->dob)->diffInYears(Carbon::now())).' Years'
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
            $item->quote_request = $item->travelQuote ?? $item->quoteRequest;
            $item->payment_date = isset($item->captured_at) ? Carbon::parse($item->captured_at)->format($dateFormat) : '';
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
