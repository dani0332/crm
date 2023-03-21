<?php

namespace App\Http\Requests;

use App\Enums\quoteTypeCode;
use App\Services\LifeQuoteService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLifeRequest extends FormRequest
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
        $travelService = (app()->make(LifeQuoteService::class));
        $travelService->getGenericModel(quoteTypeCode::Life);
        $properties = $travelService->getFieldsToCreate('skipProperties', 'update');
        $requireProperties = array_filter($properties, function ($value) {
            return strpos($value, 'required') !== false;
        });

        $rules = [];
        foreach ($requireProperties as $key => $value) {
            $rule = ['required'];
            if ($key == 'email' || $key == 'mobile_no') {
                continue;
            }
            $rules[$key] = $rule;
        }

        return $rules;
    }
}
