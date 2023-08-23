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
        if ($this->ajax() && ! request()->get('onLoadCheck')) {
            return [
                'quoteType' => 'required',
                'searchType' => 'nullable',
                'searchField' => 'required_with:searchType',
                'matchFound' => 'nullable',
                'amlCreatedStartDate' => 'nullable|required_without:searchType',
                'amlCreatedEndDate' => 'nullable|required_without:searchType'
            ];
        }

        return [];
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
