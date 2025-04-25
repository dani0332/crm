<?php

namespace App\Http\Requests;

use App\Enums\quoteTypeCode;
use App\Services\TravelQuoteService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTravelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $travelService = (app()->make(TravelQuoteService::class));
        $travelService->getGenericModel(quoteTypeCode::Travel);
        $properties = $travelService->getFieldsToCreate('skipProperties', 'update');
        $requireProperties = array_filter($properties, function ($value) {
            return strpos($value, 'required') !== false;
        });
        $rules = [];
        foreach ($requireProperties as $key => $value) {
            $rule = ['required'];
            if ($key == 'first_name' || $key == 'last_name') {
                $rule[] = 'between:1,20';
            }

            if ($key == 'email') {
                $rule[] = 'email:rfc,dns';
            }
            if ($key == 'mobile_no') {
                $rule[] = 'min:7';
                $rule[] = 'max:20';
            }
            if ($key == 'departure_country_id') {
                $rule = ['required_if:has_arrived_uae,1'];
            }
            if ($key == 'destination_ids') {
                $rule = ['required_if:has_arrived_destination,0'];
            }

            $rules[$key] = $rule;
        }

        return $rules;
    }

    public function failedValidation(Validator $validator)
    {
        $errors = $validator->errors(); // Here is your array of errors
    }
}
