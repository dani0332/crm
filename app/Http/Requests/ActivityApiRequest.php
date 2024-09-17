<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivityApiRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string',
            'description' => 'required|string',
            'dueDate' => 'required|date',
            'entityUId' => 'required|string',
            'quoteTypeId' => 'required|int',
            'activityType' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Activity Title Required',
            'description.required' => 'Activity Description Required',
            'dueDate.required' => 'Activity Due Date Required',
            'dueDate.date' => 'Activity Due Date must be a valid date',
            'entityUId.required' => 'Entity UUID Required',
            'quoteTypeId.integer' => 'Quote Type ID  must be an integer',
            'quoteTypeId.required' => 'Quote Type ID Required',
            'activityType.required' => 'Activity Type Required',
            'activityType.string' => 'Activity Type must be a string',
        ];
    }
}
