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
        if ($this->ajax()) {
            return [
                'quoteType' => 'required',
                'searchType' => 'required',
                'searchField' => 'required',
                'matchFound' => 'required',
                'amlCreatedStartDate' => 'date|required|before:amlCreatedEndDate',
                'amlCreatedEndDate' => 'date|required',
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
            'amlCreatedStartDate.date' => 'Created start date is not a valid date </br>',
            'amlCreatedStartDate.required' => 'Created start date is required ',
            'amlCreatedEndDate.date' => 'Created end date is not a valid date </br>',
            'amlCreatedEndDate.required' => 'Created end date is required',
        ];
    }

}
