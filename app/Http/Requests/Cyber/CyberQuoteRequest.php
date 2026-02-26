<?php

namespace App\Http\Requests\Cyber;

use App\Enums\PermissionsEnum;
use App\Models\Emirate;
use App\Models\Nationality;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CyberQuoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(PermissionsEnum::CYBER_QUOTES_CREATE)
            || $this->user()->can(PermissionsEnum::CYBER_QUOTES_EDIT)
            || $this->user()->can(PermissionsEnum::VIEW_ALL_LEADS);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|between:1,20|regex:/^[a-zA-Z\s\-]+$/',
            'last_name' => 'required|between:1,50|regex:/^[a-zA-Z\s\-]+$/',
            'email' => 'required|email',
            'mobile_no' => 'required|string',
            'dob' => 'required|date',
            'nationality_id' => ['required', Rule::exists(Nationality::class, 'id')],
            'emirate_of_registration_id' => ['required', Rule::exists(Emirate::class, 'id')],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.regex' => 'The first name may only contain letters, spaces, and hyphens.',
            'last_name.regex' => 'The last name may only contain letters, spaces, and hyphens.',
        ];
    }
}
