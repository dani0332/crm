<?php

namespace App\Http\Requests;

class ActivityApiRequest
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
    public static function rules()
    {
        return [
            'title' => 'required|string',
            'description' => 'required|string',
            'due_date' => 'required|date',
            'entityUId' => 'required|string',
            'quoteTypeId' => 'required|int',
        ];
    }

    public static function messages()
    {
        return [
            'title.required' => 'Activity Title Required',
            'description.required' => 'Activity Description Required',
            'due_date.required' => 'Activity Due Date Required',
            'due_date.date' => 'Activity Due Date must be a valid date',
            'entityUId.required' => 'Entity UUID Required',
            'quoteTypeId.integer' => 'Quote Type ID  must be an integer',
            'quoteTypeId.required' => 'Quote Type ID Required',
        ];
    }
}
