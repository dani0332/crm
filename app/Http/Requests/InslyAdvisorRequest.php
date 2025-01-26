<?php

namespace App\Http\Requests;

use App\Models\InslyAdvisor;
use Illuminate\Foundation\Http\FormRequest;

class InslyAdvisorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'advisors' => 'required|array',
        ];

        foreach ($this->input('advisors') as $index => $advisor) {
            $rules["advisors.$index.name"] = [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($advisor) {
                    $userId = $advisor['user_id'];
                    $exists = InslyAdvisor::where('name', $value)
                        ->where('user_id', '!=', $userId)
                        ->select('id')
                        ->limit(1)
                        ->exists();

                    if ($exists) {
                        $fail('The '.$attribute.' has already been taken.');
                    }
                },
            ];
        }

        return $rules;
    }

    /**
     * Get the custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'advisors.*.name.unique' => 'The advisor name :input is already assigned to another user.',
        ];
    }
}
