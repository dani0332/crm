<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EpCancellationCallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'etId' => ['required', 'integer', 'exists:embedded_transactions,id'],
            'quoteId' => ['required', 'integer', 'min:1'],
            'quoteTypeId' => ['required', 'integer', 'min:1'],
        ];
    }
}
