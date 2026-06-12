<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BirdStopWorkFlowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'flowType' => 'required|string',
            'uuid' => 'required|string',
            'workflowId' => 'required|string',
            'stop_source' => 'nullable|string|max:64',
        ];
    }
}
