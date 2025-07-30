<?php

namespace App\Http\Requests\V2\Admin\PrivateClientConfig;

use App\Models\QuoteType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetPrivateClientConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quote_type_id' => ['required', 'integer', Rule::exists(QuoteType::class, 'id')],
            'version' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }
}
