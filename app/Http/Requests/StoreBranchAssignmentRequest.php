<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreBranchAssignmentRequest extends FormRequest
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
            'branch_id' => 'required|integer|exists:branches,id',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'is_primary' => 'nullable|boolean',
        ];
    }

    /**
     * Handle a passed validation attempt.
     */
    public function passedValidation(): void
    {
        $user = $this->route('user');
        $activeBranches = $user->userBranches->where('status', 1);

        // Check if trying to set as primary when there's already a primary branch
        if ($this->input('is_primary')) {
            $existingPrimary = $activeBranches->where('is_primary', true)->count();
            if ($existingPrimary) {
                throw new HttpResponseException(
                    redirect()
                        ->back()
                        ->withInput()
                        ->with('error', 'This user already has a primary branch.')
                );
            }
        }

        // Check if branch is already assigned
        if ($this->input('branch_id')) {
            $existingAssignment = $activeBranches
                ->where('branch_id', $this->input('branch_id'))
                ->count();

            if ($existingAssignment) {
                throw new HttpResponseException(
                    redirect()
                        ->back()
                        ->withInput()
                        ->with('error', 'This branch is already assigned to the user.')
                );
            }
        }
    }
}
