<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;
use App\Enums\GenericRequestEnum;
use App\Enums\QuoteTypes;

class ExportValidationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [];

        $exportType = $this->route('exportType'); 

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
            $diffInDays = 120;
            $exportTye = $this->route('exportTye');
            $quoteType = $this->input('quoteType'); 

            if (ucfirst($quoteType) == QuoteTypes::CAR->value) {
                $diffInDays = 31;
            }

            if ($exportTye == GenericRequestEnum::EXPORT_PLAN_DETAIL) {
                $start = Carbon::parse($this->input('paid_at_start'));
                $end = Carbon::parse($this->input('paid_at_end'));
                $diff = $start->diffInDays($end);
                if ($diff > $diffInDays) {
                    $validator->errors()->add('error', 'Maximum of '.$diffInDays.' days are allowed to be exported.');
                }
            } else {
                if (request()->has('payment_due_date')) {
                    $start = Carbon::parse($this->input('payment_due_date')[0])->startOfDay();
                    $end = Carbon::parse($this->input('payment_due_date')[1])->endOfDay();
                    $diff = $start->diffInDays($end);
                    if ($diff > $diffInDays) {
                        $validator->errors()->add('error', 'Maximum of '.$diffInDays.' days are allowed to be exported.');
                    }
                } 
                if (request()->has('booking_date')) {
                    $start = Carbon::parse($this->input('booking_date')[0])->startOfDay();
                    $end = Carbon::parse($this->input('booking_date')[1])->endOfDay();
                    $diff = $start->diffInDays($end);
                    if ($diff > $diffInDays) {
                        $validator->errors()->add('error', 'Maximum of '.$diffInDays.' days are allowed to be exported.');
                    }
                }
                if ($this->has('created_at_start') && $this->has('created_at_end')) {
                    $start = Carbon::parse($this->input('created_at_start'));
                    $end = Carbon::parse($this->input('created_at_end'));
                    $diff = $start->diffInDays($end);
                    if ($diff > $diffInDays) {
                        $validator->errors()->add('error', 'Maximum of '.$diffInDays.' days are allowed to be exported.');
                    }
                }
            }
        });
    }
}
