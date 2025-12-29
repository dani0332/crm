<?php

namespace App\Http\Requests;

use App\Enums\QuoteTypes;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ReEvaluatePrivateClientRequest extends FormRequest
{
    private const MAX_CREATED_AT_RANGE_DAYS = 7;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'quote_type_id' => [
                'required',
                'integer',
                Rule::in([
                    QuoteTypes::CAR->id(),
                    /*QuoteTypes::HEALTH->id(),
                    QuoteTypes::LIFE->id(),
                    QuoteTypes::YACHT->id(),
                    QuoteTypes::HOME->id(),*/
                ]),
            ],
            'lead_uuids' => ['required_without:created_at', 'array'],
            'lead_uuids.*' => ['string'],
            'is_policy_booked' => ['sometimes', 'boolean'],
            'is_pcp_assigned' => ['sometimes', 'boolean'],
            'created_at' => ['required_without:lead_uuids', 'array'],
            'created_at.start' => ['required_with:created_at', 'date_format:Y-m-d'],
            'created_at.end' => ['required_with:created_at', 'date_format:Y-m-d', 'after_or_equal:created_at.start'],
        ];
    }

    public function messages(): array
    {
        return [
            'quote_type_id.in' => 'Quote type is not eligible for PCP evaluation.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $createdAt = $this->input('created_at');

            if (! is_array($createdAt) || empty($createdAt['start']) || empty($createdAt['end'])) {
                return;
            }

            try {
                $start = Carbon::createFromFormat('Y-m-d', $createdAt['start']);
                $end = Carbon::createFromFormat('Y-m-d', $createdAt['end']);
            } catch (\Exception) {
                return;
            }

            $rangeDaysInclusive = $start->diffInDays($end) + 1;

            if ($rangeDaysInclusive > self::MAX_CREATED_AT_RANGE_DAYS) {
                $validator->errors()->add(
                    'created_at',
                    "The created_at range may not exceed ".self::MAX_CREATED_AT_RANGE_DAYS." days (inclusive)."
                );
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        $response = apiResponse(
            $validator->errors()->toArray(),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Validation failed.'
        );

        throw new HttpResponseException($response);
    }
}
