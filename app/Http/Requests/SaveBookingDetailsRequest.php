<?php

namespace App\Http\Requests;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypeId;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\Payment;
use App\Models\SendUpdateLog;
use App\Repositories\InsuranceProviderRepository;
use App\Rules\NotZero;
use App\Services\SendUpdateLogService;
use Illuminate\Foundation\Http\FormRequest;

class SaveBookingDetailsRequest extends FormRequest
{
    protected object $sendUpdate;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'id' => 'required|exists:send_update_logs,id',
            'commission_vat_applicable' => 'required|numeric',
            'invoice_description' => 'required|string',
            'invoice_date' => 'required|date',
            'insurer_tax_invoice_number' => 'required|string|max:50',
            'insurer_commission_invoice_number' => 'required|string|max:50',
            'discount' => 'nullable|numeric',
            'commission_percentage' => 'required|numeric',
            'commission_vat_not_applicable' => 'required|numeric',
            'vat_on_commission' => 'required|numeric',
            'total_commission' => 'required|numeric',
            'total_vat_amount' => 'sometimes|numeric',
            'price_vat_applicable' => 'sometimes|numeric',
            'price_vat_not_applicable' => 'sometimes|numeric',
            'total_price' => 'sometimes|numeric',
            'price_with_vat' => 'required|numeric',
            'broker_invoice_number' => 'sometimes',
            'transaction_payment_status' => 'required|string',
            'reversal_invoice' => 'sometimes',
        ];

        $this->sendUpdate = SendUpdateLog::where('id', request()->id ?? '')->firstOrFail();

        //        Price not applicable enabled when the endorsement will be Life, Business, Health
        //        Price vat applicable and total vat amount enabled when the endorsement will not be Life

        $validatedCatForPrices = in_array($this->sendUpdate->category?->code, [SendUpdateLogStatusEnum::EF, SendUpdateLogStatusEnum::CPD, SendUpdateLogStatusEnum::CI,
            SendUpdateLogStatusEnum::CIR]);

        if (request()->input('commission_vat_not_applicable') > 0) {
            $rules['commission_vat_applicable'] = 'nullable|numeric';
            $rules['vat_on_commission'] = 'nullable';
        }

        if (request()->input('commission_vat_applicable') > 0) {
            $rules['commission_vat_not_applicable'] = 'nullable|numeric';
        }

        if ($validatedCatForPrices) {
            if (! in_array($this->sendUpdate->quote_type_id, [QuoteTypeId::Life, QuoteTypeId::Business, QuoteTypeId::Health])) {
                $rules['price_vat_applicable'] = ['required', 'numeric', new NotZero];
                $rules['total_vat_amount'] = 'required|numeric';
            }

            if ($this->sendUpdate->quote_type_id == QuoteTypeId::Life) {
                $rules['price_vat_not_applicable'] = ['required', 'numeric', new NotZero];
            }

            if (request()->input('price_vat_applicable') !== 0) {
                $rules['total_vat_amount'] = 'required|numeric';
            }
        }

        if ($this->sendUpdate->category?->code == SendUpdateLogStatusEnum::CPD) {
            $rules['reversal_invoice'] = 'required|string';
        }

        if ($this->get('send_update_option') !== null && in_array($this->get('send_update_option'), [SendUpdateLogStatusEnum::ACB, SendUpdateLogStatusEnum::ATCRNB_RBB])) {
            $skipRules = ['insurer_tax_invoice_number', 'total_vat_amount', 'commission_percentage', 'price_vat_applicable', 'price_vat_not_applicable', 'total_price'];
            $rules = array_diff_key($rules, array_flip($skipRules));
        }

        if ($this->get('send_update_option') !== null && in_array($this->get('send_update_option'), [SendUpdateLogStatusEnum::ATIB, SendUpdateLogStatusEnum::ATCRNB])) {
            $skipRules = ['insurer_commission_invoice_number', 'vat_on_commission', 'commission_percentage', 'commission_vat_applicable', 'commission_vat_not_applicable', 'total_commission'];
            $rules = array_diff_key($rules, array_flip($skipRules));
        }

        [$insuranceProviderId, $planId] = app(SendUpdateLogService::class)->getEndorsementProviderDetails($this->sendUpdate);
        $insuranceProvider = InsuranceProviderRepository::find($insuranceProviderId);

        if ($insuranceProvider?->non_self_billing) {
            $rules['broker_invoice_number'] = 'required|string';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->sendUpdate = SendUpdateLog::where('id', request()->id ?? '')->firstOrFail();

            //        Price not applicable enabled when the endorsement will be Life, Business, Health
            //        Price vat applicable and total vat amount enabled when the endorsement will not be Life

            $validatedCatForPrices = in_array($this->sendUpdate->category?->code, [
                SendUpdateLogStatusEnum::EF,
                SendUpdateLogStatusEnum::CPD,
                SendUpdateLogStatusEnum::CI,
                SendUpdateLogStatusEnum::CIR,
            ]);

            $isAdditionalCommission = in_array($this->sendUpdate->option?->code, [SendUpdateLogStatusEnum::ACB, SendUpdateLogStatusEnum::ATCRNB_RBB]);

            if ($validatedCatForPrices && ! $isAdditionalCommission && in_array($this->sendUpdate->quote_type_id, [QuoteTypeId::Business, QuoteTypeId::Health]) &&
                request()->input('price_vat_applicable') == 0 && request()->input('price_vat_not_applicable') == 0) {
                $validator->errors()->add('value', 'One of the fields, either "price vat applicable" or "price vat not applicable", must be greater than 0');
            }

            if ($this->sendUpdate->status == SendUpdateLogStatusEnum::UPDATE_BOOKING_FAILED && ! auth()->user()->can(PermissionsEnum::BOOKING_FAILED_EDIT)) {
                $validator->errors()->add('error', 'Endorsement Booking Failed! Please contact finance');
            }

            if ($this->sendUpdate->status == SendUpdateLogStatusEnum::UPDATE_BOOKING_QUEUED) {
                return $validator->errors()->add('error', 'Update booking already in queued');
            }

            $insurerTaxInvoiceNumber = request()->insurer_tax_invoice_number;
            $insurerCommissionInvoiceNumber = request()->insurer_commission_invoice_number;

            // if it's CPD and insurer tax invoice or commission invoice is not empty.
            if ($this->sendUpdate->category?->code == SendUpdateLogStatusEnum::CPD && ($insurerTaxInvoiceNumber || $insurerCommissionInvoiceNumber)) {
                $payment = Payment::where('insurer_tax_number', request()->reversal_invoice)->first();
                if (
                    (($payment?->insurer_tax_number.'-REV') == $insurerTaxInvoiceNumber) ||
                    (($payment?->insurer_commmission_invoice_number.'-REV') == $insurerCommissionInvoiceNumber)
                ) {
                    $validator->errors()->add('error', 'Reversal Document Number should not be the same as the New Document Number.');
                }
            }

            // if insurer tax invoice is not empty.
            if ($insurerTaxInvoiceNumber) {
                $taxInvoiceValidation = Payment::select('id')
                    ->where(function ($query) {
                        $query->whereNot('send_update_log_id', $this->sendUpdate->id)
                            ->orWhereNull('send_update_log_id');
                    })
                    ->where(function ($query) use ($insurerTaxInvoiceNumber) {
                        $query->where('insurer_tax_number', $insurerTaxInvoiceNumber)
                            ->orWhere('insurer_commmission_invoice_number', $insurerTaxInvoiceNumber);
                    })->first() ||
                    SendUpdateLog::select('id')->whereNot('uuid', $this->sendUpdate->uuid)
                        ->where(function ($query) use ($insurerTaxInvoiceNumber) {
                            $query->where('insurer_tax_invoice_number', $insurerTaxInvoiceNumber)
                                ->orWhere('insurer_commission_invoice_number', $insurerTaxInvoiceNumber);
                        })
                        ->first();

                if ($taxInvoiceValidation) {
                    $validator->errors()->add('error', 'Insurer Tax Invoice Number already exists, Please enter a unique value.');
                }
            }

            // if insurer commission invoice is not empty.
            if ($insurerCommissionInvoiceNumber) {
                $commissionInvoiceValidation = Payment::select('id')
                    ->where(function ($query) {
                        $query->whereNot('send_update_log_id', $this->sendUpdate->id)
                            ->orWhereNull('send_update_log_id');
                    })
                    ->where(function ($query) use ($insurerCommissionInvoiceNumber) {
                        $query->where('insurer_commmission_invoice_number', $insurerCommissionInvoiceNumber)
                            ->orWhere('insurer_tax_number', $insurerCommissionInvoiceNumber);
                    })->first() ||
                    SendUpdateLog::select('id')->whereNot('uuid', $this->sendUpdate->uuid)
                        ->where(function ($query) use ($insurerCommissionInvoiceNumber) {
                            $query->where('insurer_commission_invoice_number', $insurerCommissionInvoiceNumber)
                                ->orWhere('insurer_tax_invoice_number', $insurerCommissionInvoiceNumber);
                        })
                        ->first();

                if ($commissionInvoiceValidation) {
                    $validator->errors()->add('error', 'Insurer Commission Invoice Number already exists, Please enter a unique value.');
                }
            }

            // if insurer tax invoice and commission invoice are not null and bother are same.
            if (($insurerTaxInvoiceNumber && $insurerCommissionInvoiceNumber) && ($insurerTaxInvoiceNumber == $insurerCommissionInvoiceNumber)) {
                $validator->errors()->add('error', 'Insurer Tax Invoice Number and Insurer Commission Invoice Number should not be the same.');
            }
        });
    }
}
