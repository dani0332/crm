<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AMLRequest extends FormRequest
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
        $rules = [
            'quoteType' => 'nullable',
            'searchType' => 'nullable',
            'searchField' => 'nullable',
            'matchFound' => 'nullable',
            'amlCreatedStartDate' => 'nullable',
            'amlCreatedEndDate' => 'nullable',
        ];

        if ($this->ajax() && ! request()->get('onLoadCheck') && ! empty(request()->toArray())) {
            $rules['quoteType'] = 'required';
            $rules['searchType'] = 'nullable';
            $rules['searchField'] = 'required_with:searchType';
            $rules['matchFound'] = 'nullable';
            $rules['amlCreatedStartDate'] = 'nullable|required_without:searchType';
            $rules['amlCreatedEndDate'] = 'nullable|required_without:searchType';
        }

        return $rules;
    }

    /**
     * Get the validation rule messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'quoteType.required' => 'Quote Type is required',
            'searchField.required' => 'Search Value is required',
            'amlCreatedStartDate.required' => 'Created start date is required ',
            'amlCreatedEndDate.required' => 'Created end date is required',
        ];
    }

}
