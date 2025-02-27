<?php

namespace App\Http\Requests;

use App\Enums\quoteTypeCode;
use App\Services\TravelQuoteService;
use Illuminate\Foundation\Http\FormRequest;

class StoreTravelRequest extends FormRequest
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
        $editMode = request()->edit_mode ?? false;
        $travelService = (app()->make(TravelQuoteService::class));
        $travelService->getGenericModel(quoteTypeCode::Travel);
        $properties = $travelService->getFieldsToCreate('skipProperties', 'create');
        $requireProperties = array_filter($properties, function ($value) {
            return strpos($value, 'required') !== false;
        });
        if ($editMode) {
            unset($requireProperties['members']);
        }
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
            if ($key == 'members') {
                $rules[$key.'.*.dob'][] = 'required_if:has_arrived_uae,0';
                $rules[$key.'.*.dob'][] = 'required_if:has_arrived_destination,0';
                $rules[$key.'.*.gender'][] = 'required_if:has_arrived_uae,0';
                $rules[$key.'.*.gender'][] = 'required_if:has_arrived_destination,0';
            }
            if ($key == 'departure_country_id') {
                $rule = ['required_if:has_arrived_uae,1'];
            }

            $rules[$key] = $rule;
        }

        return $rules;
    }
}
