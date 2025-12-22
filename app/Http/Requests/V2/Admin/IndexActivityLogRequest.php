<?php

declare(strict_types=1);

namespace App\Http\Requests\V2\Admin;

use App\Enums\ActivityLogEventEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexActivityLogRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'user_id' => 'nullable|integer|exists:users,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'event' => ['nullable', 'string', Rule::in(ActivityLogEventEnum::getValues())],
        ];
    }

    /**
     * Get filtered values, removing empty/null values.
     */
    public function getFilters(): array
    {
        $filters = [
            'user_id' => $this->input('user_id'),
            'date_from' => $this->input('date_from'),
            'date_to' => $this->input('date_to'),
            'event' => $this->input('event'),
        ];

        return array_filter($filters, fn($value) => !empty($value));
    }

    /**
     * Get filters for Inertia response (including null values).
     */
    public function getFiltersForResponse(): array
    {
        return $this->only([
            'user_id',
            'date_from',
            'date_to',
            'event',
        ]);
    }
}

