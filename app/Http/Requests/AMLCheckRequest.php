<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AMLCheckRequest extends FormRequest
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
        return [
            'insured_first_name' => 'required|max:200',
            'insured_last_name' => 'required|max:200',
            'nationality' => 'required',
            'date_of_birth' => 'required',
            'customer_id' => 'required'
        ];
    }
}
