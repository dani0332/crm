<?php

namespace App\Http\Requests;

use App\Enums\quoteTypeCode;
use App\Services\TravelQuoteService;
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
            if ($key == 'first_name' || $key == 'last_name') {
                $rule[] = 'max:255';
            } else {
                $rule[] = 'max:1000';
            }

            $rule = ['required'];
            if ($key == 'email' || $key == 'mobile_no') {
                continue;
            }
            $rules[$key] = $rule;
        }

        return $rules;
    }
}
