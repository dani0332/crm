<?php

namespace App\Http\Requests;

use http\Env\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class LeadAssignRequest extends FormRequest
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
            'assigned_to_id_new' => 'required|exists:App\Models\User,id',
            'selectTmLeadId' => 'sometimes|required',
            'entityId' => 'sometimes|required'
        ];
    }

    public function attributes()
    {
        return [
            'assigned_to_id_new' => 'user to assign leads',
            'selectTmLeadId' => 'lead(s) to assign',
            'entityId' => 'lead(s) to assign',
        ];
    }
}
