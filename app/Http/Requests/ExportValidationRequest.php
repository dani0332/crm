<?php

namespace App\Http\Requests;

use App\Enums\GenericRequestEnum;
use App\Enums\QuoteTypes;
use App\Enums\RetentionReportEnum;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ExportValidationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [];

        $exportType = $this->route('quoteType');
        $quoteType = $this->route('quoteType');

        if ($exportType != GenericRequestEnum::EXPORT_MAKES_MODELS) {
            if ($exportType == GenericRequestEnum::EXPORT_PLAN_DETAIL) {
                $rules = [
                    'paid_at_start' => 'required|date',
                    'paid_at_end' => 'required|date',
                ];
            } elseif ($this->has('payment_due_date')) {
                $rules['payment_due_date.*'] = 'required|date';
            } elseif ($this->has('booking_date')) {
                $rules['booking_date.*'] = 'required|date';
            } elseif ($quoteType == RetentionReportEnum::RETENTION) {
                $rules = [
                    'lob' => 'required',
                    'displayBy' => 'required',
                    'policyExpiryDate.*' => 'required|date',
                ];
            } elseif ($this->has('transaction_approved_dates')) {
                $rules['transaction_approved_dates.*'] = 'required|date';
            } else {
                $rules = [
                    'created_at_start' => 'required|date',
                    'created_at_end' => 'required|date',
                ];
            }
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if (! $validator->errors()->any()) {
                $diffInDays = 120;
                $exportTye = $this->route('exportTye');
                $quoteType = $this->route('quoteType');

                if ((ucfirst($quoteType) == QuoteTypes::CAR->value) || $quoteType == RetentionReportEnum::RETENTION) {
                    $diffInDays = 31;
                }

                if ($exportTye != GenericRequestEnum::EXPORT_MAKES_MODELS) {
                    if ($exportTye == GenericRequestEnum::EXPORT_PLAN_DETAIL) {
                        $start = Carbon::parse($this->input('paid_at_start'));
                        $end = Carbon::parse($this->input('paid_at_end'));
                        $error_fields = 'paid at';
                    } elseif (request()->has('payment_due_date')) {
                        $start = Carbon::parse($this->input('payment_due_date')[0])->startOfDay();
                        $end = Carbon::parse($this->input('payment_due_date')[1])->endOfDay();
                        $error_fields = 'payment due date';
                    } elseif (request()->has('booking_date')) {
                        $start = Carbon::parse($this->input('booking_date')[0])->startOfDay();
                        $end = Carbon::parse($this->input('booking_date')[1])->endOfDay();
                        $error_fields = 'booking date';
                    } elseif ($this->has('created_at_start') && $this->has('created_at_end')) {
                        $start = Carbon::parse($this->input('created_at_start'));
                        $end = Carbon::parse($this->input('created_at_end'));
                        $error_fields = 'created date';
                    } elseif ($quoteType == RetentionReportEnum::RETENTION) {
                        if ($this->input('policyExpiryDate')) {
                            $diffInDays = 92;
                            $start = Carbon::parse($this->input('policyExpiryDate')[0])->startOfDay();
                            $end = Carbon::parse($this->input('policyExpiryDate')[1])->endOfDay();
                            $error_fields = 'start & end date';
                        }
                    } elseif ($this->has('transaction_approved_dates')) {
                        $start = Carbon::parse($this->input('transaction_approved_dates')[0])->startOfDay();
                        $end = Carbon::parse($this->input('transaction_approved_dates')[1])->endOfDay();
                        $error_fields = 'transaction approved dates';
                    }
                    $diff = $start->diffInDays($end);
                    if ($diff > $diffInDays) {
                        $validator->errors()->add('flash', 'Maximum of '.$diffInDays.' days ('.$error_fields.') are allowed to be exported.');
                    }
                } else {
                    $validator->errors()->add('flash', 'Valid dates are required to export.');
                }
            }
        });
    }
}
