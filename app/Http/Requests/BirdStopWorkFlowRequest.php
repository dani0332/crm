<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BirdStopWorkFlowRequest extends FormRequest
{
    public function authorize()
    {
        // Allow or deny access
        return true;
    }

    public function rules()
    {
        return [
            'flowType' => 'nullable|string',
            'uuid' => 'nullable|string',
            'flowId' => 'nullable|string',
        ];
    }
}
