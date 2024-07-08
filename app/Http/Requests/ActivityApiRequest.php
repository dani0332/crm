<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'assignee_id' => 'required|integer',
            'entityUId' => 'required|string',
            'entityId' => 'required|integer',
            'modelType' => 'required|string',
        ];
    }

    public static function messages()
    {
        return [
            'title.required' => 'Activity Title Required',
            'description.required' => 'Activity Description Required',
            'due_date.required' => 'Activity Due Date Required',
            'due_date.date' => 'Activity Due Date must be a valid date',
            'assignee_id.required' => 'Activity Assignee ID Required',
            'assignee_id.integer' => 'Activity Assignee ID must be an integer',
            'entityUId.required' => 'Entity UUID Required',
            'entityId.required' => 'Entity ID Required',
            'entityId.integer' => 'Entity ID must be an integer',
            'modelType.required' => 'Model Type Required',
        ];
    }
}
