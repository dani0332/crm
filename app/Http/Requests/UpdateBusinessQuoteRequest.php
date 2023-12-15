<?php

namespace App\Http\Requests;

use App\Enums\quoteTypeCode;
use App\Services\BusinessQuoteService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessQuoteRequest extends FormRequest
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
        $travelService = (app()->make(BusinessQuoteService::class));
        $travelService->getGenericModel(quoteTypeCode::Business);
        $properties = $travelService->getFieldsToCreate('skipProperties', 'update');
        $requireProperties = array_filter($properties, function ($value) {
            return strpos($value, 'required') !== false;
        });

        $rules = [];
        foreach ($requireProperties as $key => $value) {
            $rule = ['required'];
            if ($key == 'email') {
                $rule[] = 'email:rfc,dns';
            }
            if ($key == 'phone') {
                $rule[] = 'regex:/(0)[0-9]/';
                $rule[] = 'not_regex:/[a-z]/';
                $rule[] = 'min:7';
                $rule[] = 'max:20';
            }
            if ($key == 'number_of_employees') {
                $rule = 'required|numeric|max:2147483645';
            }
            $rules[$key] = $rule;
        }

        return $rules;
    }
}
