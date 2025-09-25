<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateBranchRequest extends FormRequest
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
            'name' => 'required|unique:branches,name,'.$this->branch->id,
            'code' => 'required|unique:branches,code,'.$this->branch->id,
            'type' => 'required|string',
            'status' => 'required|boolean',
        ];
    }

    /**
     * Handle a passed validation attempt.
     */
    public function passedValidation(): void
    {
        if ($this->status != $this->branch->status) {
            $activeUserBranches = $this->branch->userBranches->where('status', 1);
            if ($activeUserBranches->count() > 0) {

                throw new HttpResponseException(
                    redirect()
                        ->back()
                        ->withInput()
                        ->with('error', 'Branch cannot be deactivated because it has active users')
                );
            }
        }
    }
}
