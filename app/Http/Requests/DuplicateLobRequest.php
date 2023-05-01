<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DuplicateLobRequest extends FormRequest
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
            'modelType' => 'nullable',
            'parentType' => 'nullable',
            'entityId' => 'nullable',
            'entityCode' => 'nullable',
            'entityUId' => 'nullable',
            'lob_team' => 'nullable',
            'lob_team_sub_selection' => 'nullable',
        ];
    }
}
