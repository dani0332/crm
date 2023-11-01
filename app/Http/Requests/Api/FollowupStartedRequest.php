<?php

namespace App\Http\Requests\Api;

use App\Rules\ValidateQuoteObject;
use Illuminate\Foundation\Http\FormRequest;

class FollowupStartedRequest extends FormRequest
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
     *
     * @return array
     */
    public function rules()
    {
        return [
            'quote_type' => 'required',
            'quote_uuid' => ['required', new ValidateQuoteObject],
            'quote_status_id' => 'required|exists:quote_status,id',
            'notes' => 'nullable',
            'followup_id' => 'required',
        ];
    }
}
