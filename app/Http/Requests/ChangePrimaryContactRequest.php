<?php

namespace App\Http\Requests;

use App\Enums\GenericRequestEnum;
use Illuminate\Foundation\Http\FormRequest;

class ChangePrimaryContactRequest extends FormRequest
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
            'key' => 'required|in:'.GenericRequestEnum::EMAIL.','.GenericRequestEnum::MOBILE_NO,
            'value' => 'required',
            'quote_id' => 'required',
        ];
    }
}
