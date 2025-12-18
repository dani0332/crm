<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\DeviceFailureTypeEnum;
use Illuminate\Foundation\Http\FormRequest;

class DeviceFailureEmailRequest extends FormRequest
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
            'quote_uuid' => 'required|string',
            'failure_type' => 'required|string|in:'.DeviceFailureTypeEnum::AUTO_CAPTURE_PAYMENT->value,
            'provider_code' => 'nullable|string',
            'reason' => 'nullable|string',
        ];
    }
}
