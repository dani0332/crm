<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

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
            'dueDate' => 'sometimes|date',
            'entityUId' => 'required|string',
            'quoteTypeId' => 'required|int',
            'activityType' => 'sometimes|string',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Activity Title Required',
            'description.required' => 'Activity Description Required',
            'dueDate.date' => 'Activity Due Date must be a valid date',
            'entityUId.required' => 'Entity UUID Required',
            'quoteTypeId.integer' => 'Quote Type ID  must be an integer',
            'quoteTypeId.required' => 'Quote Type ID Required',
            'activityType.string' => 'Activity Type must be a string',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(['message' => $validator->errors()], 422));
    }
}
