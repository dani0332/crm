<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RenewalBatchRequest extends FormRequest
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
            'name' => ['required', \Illuminate\Validation\Rule::unique('renewal_batches')->where(function ($query) {
                $query = $query->where('quote_status_id', request()->quote_status_id);
                if (! empty(request()->renewal_batch)) {
                    $query->where('id', '<>', request()->renewal_batch);
                }

                return $query;
            })],
            'quote_status_id' => 'required|integer',
            'deadline_date' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'name.unique' => 'Batch name and lead status already exists.',
        ];
    }
}
