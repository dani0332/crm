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

    /**
     * Prepare the data for validation by converting "null" strings to null.
     */
    protected function prepareForValidation()
    {
        $data = $this->all();

        // Recursively replace "null" strings with null
        array_walk_recursive($data, function (&$value) {
            if ($value === 'null') {
                $value = null;
            }
        });

        // Merge the modified data back into the request
        $this->merge($data);
        request()->merge($data); // Sync with global request
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
            } elseif ($this->filled('renewal_batch')) {
                // If renewal batch is provided, no other date fields are required
                $rules = [
                    'created_at_start' => 'nullable|date',
                    'created_at_end' => 'nullable|date',
                    'policy_expiry_date' => 'nullable|date',
                    'policy_expiry_date_end' => 'nullable|date',
                ];
            } else {
                $rules = [
                    'created_at_start' => 'nullable|required_without:policy_expiry_date,policy_expiry_date_end|date',
                    'created_at_end' => 'nullable|required_without:policy_expiry_date,policy_expiry_date_end|date',
                    'policy_expiry_date' => 'nullable|required_without:created_at_start,created_at_end|date',
                    'policy_expiry_date_end' => 'nullable|required_without:created_at_start,created_at_end|date',
                ];
            }
        }

        return $rules;
    }

    /**
     * Calculate months difference between two dates
     */
    private function calculateMonthsDifference($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Calculate year and month difference
        $yearDiff = $end->year - $start->year;
        $monthDiff = $end->month - $start->month;

        // Total months difference
        $totalMonths = $yearDiff * 12 + $monthDiff;

        // Return absolute difference in months
        // (e.g., March 31 to April 1 = 1 month, March 15 to March 20 = 0 months)
        return abs($totalMonths);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if (! $validator->errors()->any()) {
                $diffInDays = 31;
                $exportTye = $this->route('exportTye');
                $quoteType = $this->route('quoteType');
                $isEmailExport = $this->input('exportType') === 'email';

                if ((ucfirst($quoteType) == QuoteTypes::CAR->value) || $quoteType == RetentionReportEnum::RETENTION) {
                    $diffInDays = 31;
                }

                if ($exportTye != GenericRequestEnum::EXPORT_MAKES_MODELS) {
                    $start = null;
                    $end = null;

                    if ($exportTye == GenericRequestEnum::EXPORT_PLAN_DETAIL) {
                        $start = Carbon::parse($this->input('paid_at_start'));
                        $end = Carbon::parse($this->input('paid_at_end'));
                        $error_fields = 'paid at';
                    } elseif (request()->has('payment_due_date')) {
                        $start = Carbon::parse($this->input('payment_due_date')[0])->startOfDay();
                        $end = Carbon::parse($this->input('payment_due_date')[1])->endOfDay();
                        $error_fields = 'payment due date';
                    } elseif (request()->filled('booking_date')) {
                        $start = Carbon::parse($this->input('booking_date')[0])->startOfDay();
                        $end = Carbon::parse($this->input('booking_date')[1])->endOfDay();
                        $error_fields = 'booking date';
                    } elseif ($this->has('created_at_start') && $this->has('created_at_end')) {
                        $start = Carbon::parse($this->input('created_at_start'));
                        $end = Carbon::parse($this->input('created_at_end'));
                        $error_fields = 'created date';
                    } elseif ($this->filled('policy_expiry_date') && $this->filled('policy_expiry_date_end')) {
                        $start = Carbon::parse($this->input('policy_expiry_date'));
                        $end = Carbon::parse($this->input('policy_expiry_date_end'));
                        $error_fields = 'policy expiry date';
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

                    if ($start && $end) {
                        if ($isEmailExport) {
                            // For email exports, use months-based validation
                            $monthsDiff = $this->calculateMonthsDifference($start, $end);
                            $maxMonths = 3;

                            if ($monthsDiff > $maxMonths) {
                                $message = "Maximum of {$maxMonths} months ({$error_fields}) are allowed to be exported.";
                                $validator->errors()->add('flash', $message);
                            }
                        } else {
                            // For download exports, use days-based validation
                            $diff = $start->diffInDays($end);
                            if ($diff >= $diffInDays) {
                                $message = "Maximum of {$diffInDays} days ({$error_fields}) are allowed to be exported.";
                                $validator->errors()->add('flash', $message);
                            }
                        }
                    }

                } else {
                    $validator->errors()->add('flash', 'Valid dates are required to export.');
                }
            }
        });
    }
}
