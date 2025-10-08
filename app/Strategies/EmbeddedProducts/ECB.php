<?php

namespace App\Strategies\EmbeddedProducts;

use App\Enums\PaymentStatusEnum;
use App\Models\EmbeddedTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
            'Contact Number',
            'Email ID',
            'Contribution Amount', // Payment Amount With VAT
            'POLICY ISSUE STATUS',
            'Certificate Number',
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
            $certificate->contact_number,
            $certificate->email,
            $certificate->contribution_amount,
            $certificate->status,
            $certificate->certificate_number,
            $certificate->model_year,
            $certificate->make,
            $certificate->model,
            $certificate->chassis_number,
            $certificate->excess_amount
        ];
    }

    public function filterReport($ep, $filters)
    {
        $productTransaction = EmbeddedTransaction::whereHas('product.embeddedProduct', function ($query) use ($ep) {
            $query->where('id', $ep->id);
        });
        $dataset = $productTransaction->with(
            'product.embeddedProduct',
            'quoteRequest',
            'quoteRequest.customer',
            'quoteRequest.quoteStatus',
            'quoteRequest.quoteRequestEntityMapping',
        )
            ->join('payments', function ($join) {
                $join->on('embedded_transactions.code', '=', 'payments.code')
                    ->where('payments.paymentable_type', '=', 'App\\Models\\EmbeddedTransaction');
            })
            ->join('car_quote_request_detail', function ($join) {
                $join->on('embedded_transactions.quote_request_id', '=', 'car_quote_request_detail.car_quote_request_id')
                    ->where('embedded_transactions.quote_request_type', '=', 'App\\Models\\CarQuote');
            })
            ->where('embedded_transactions.is_selected', true)
            ->where('embedded_transactions.payment_status_id', PaymentStatusEnum::CAPTURED)
            ->when(isset($filters['ref_id']), function ($query) use ($filters) {
                $query->where('embedded_transactions.code', 'like', "%{$filters['ref_id']}%");
            })
            ->when(isset($filters['chassis_number']), function ($query) use ($filters) {
                $query->where('car_quote_request_detail.chassis_number', 'like', "%{$filters['chassis_number']}%");
            })
            ->when(isset($filters['months']), function ($query) use ($filters) {
                $startDate = Carbon::parse($filters['months'])->startOfMonth()->format('Y-m-d');
                $endDate = Carbon::parse($filters['months'])->endOfMonth()->format('Y-m-d');
                $query->whereBetween('payments.captured_at', [$startDate, $endDate]);

            })
            ->when(isset($filters['name']), function ($query) use ($filters) {
                $name = $filters['name'];

                $query->whereHas('quoteRequest', function ($query) use ($name) {
                    $query->where('first_name', 'like', "%{$name}%")
                        ->orWhere('last_name', 'like', "%{$name}%");
                });
            })
            ->when(isset($filters['email']), function ($query) use ($filters) {
                $query->whereHas('quoteRequest', function ($query) use ($filters) {
                    $email = $filters['email'];
                    $query->where('email', 'like', "%{$email}%");
                });
            })
            ->when((isset($filters['date_of_purchase']) && count($filters['date_of_purchase'])), function ($query) use ($filters) {
                $query->whereHas('quoteRequest', function ($query) use ($filters) {
                    $startDate = Carbon::parse($filters['date_of_purchase'][0] ?? '')->startOfDay();
                    $endDate = Carbon::parse($filters['date_of_purchase'][1] ?? '')->endOfDay();
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
            $quoteObject = $item->quoteRequest;
            $status = $quoteObject->quoteStatus->text ?? '';
            $customer = $quoteObject->customer ?? null;
            $customerInsured = $customer?->customerInsured()
                ->where('quote_request_id', $item->quote_request_id)
                ->where('quote_type_id', $item->quote_type_id)
                ->latest('updated_at')
                ->first() ?? null;

            $planStartDate = (! empty($quoteObject->policy_start_date) && $quoteObject->policy_start_date != '0000-00-00 00:00:00') ? Carbon::parse($quoteObject->policy_start_date)->format($dateFormat) : '';
            $planEndDate = '';
            if (! empty($planStartDate)) {
                $planEndDate = Carbon::parse($quoteObject->policy_start_date)->addYear()->format($dateFormat);
            }

            if (! empty($quoteObject->quoteRequestEntityMapping)) {
                $firstName = $quoteObject->first_name ?? '';
                $lastName = $quoteObject->last_name ?? '';
            } else {
                $firstName = ($customerInsured?->insured?->first_name ?? $customer?->insured_first_name) ?? '';
                $lastName = ($customerInsured?->insured?->last_name ?? $customer?->insured_last_name) ?? '';
            }

            $item->id = $item->id;
            $item->ref_id = $item->code;
            $item->quote_request = $item->quoteRequest;
            $item->plan_start_date = $planStartDate;
            $item->plan_end_date = $planEndDate;
            $item->certificate_number = $item->certificate_number ?? '';
            $item->name = $firstName.' '.$lastName;
            $item->email = $quoteObject->email ?? '';
            $item->contact_number = $quoteObject->mobile_no ?? '';
            $item->emirates_id_number = ($customerInsured?->insured?->id_number ?? $customer?->emirates_id_number) ?? '';
            $item->model_year = $quoteObject->year_of_manufacture ?? '';
            $item->make = $quoteObject->carMake?->text ?? '';
            $item->model = $quoteObject->carModel?->text ?? '';
            $item->chassis_number = $quoteObject->carQuoteRequestDetail?->chassis_number ?? '';
            $item->excess_amount = $quoteObject->carQuoteRequestDetail?->excess.'/-';
            $item->payment_date = isset($item->captured_at) ? Carbon::parse($item->captured_at)->format($dateFormat) : '';
            $item->contribution_amount = 'AED '.$item->price_with_vat.'/-';
            $item->status = $status;
            return $item;
        });

        return $dataset;
    }
}
