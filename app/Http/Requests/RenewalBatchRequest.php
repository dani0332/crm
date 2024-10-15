<?php

namespace App\Http\Requests;

use App\Enums\RolesEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RenewalBatchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::check() && Auth::user()->hasAnyRole([RolesEnum::RenewalsManager, RolesEnum::Admin]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $rules = [];

        // Get the slab array from the request data
        $slabArray = $this->input('slab');
        $optionalSlabsId = $this->input('optional_slabs') ?? [];
        $optionalTeamsId = $this->input('optional_teams') ?? [];

        $slabIndex = count($slabArray);

        // Define the validation rules
        if ($slabIndex > 0) {
            $i = $slabIndex;
            while ($i >= 1) {

                if (in_array($i, $optionalSlabsId)) {
                    $rules['slab.'.$i.''] = 'nullable|sometimes|array';
                } else {
                    $rules['slab.'.$i.''] = 'required|array';
                }

                $previousSlabIndex = $i - 1;
                $teamIds = array_keys($slabArray[$i]);

                foreach ($teamIds as $teamId) {

                    $flexRequired = (in_array($teamId, $optionalTeamsId) &&
                        in_array($i, $optionalSlabsId)
                    ) ?
                        ['sometimes', 'nullable'] : ($previousSlabIndex === 0 ? ['required', 'numeric'] :
                            ['required', 'numeric', 'gt:'.$slabArray[$previousSlabIndex][$teamId]['Max'], 'lt:'.($slabArray[$previousSlabIndex][$teamId]['Max'] + 2)]
                        );

                    $rules['slab.'.$i.'.'.$teamId.'.Min'] = $flexRequired;
                }

                $teamIds = array_keys($slabArray[$i]);
                foreach ($teamIds as $teamId) {

                    $flexRequired = (in_array($teamId, $optionalTeamsId) &&
                        in_array($i, $optionalSlabsId)
                    ) ?
                        ['sometimes', 'nullable'] :
                        ['required', 'numeric', 'gt:'.$slabArray[$i][$teamId]['Min']];

                    $rules['slab.'.$i.'.'.$teamId.'.Max'] = $flexRequired;
                }

                $i--;
                if ($i === 1) {
                    $rules['slab.'.$i.''] = 'required|array';
                }
            }
        }

        $simpleRules = [
            'name' => [
                'required',
                'max:240',
                Rule::unique('renewal_batches')->ignore($this->id),
            ],
            'start_date' => [
                'required',
                'date',
            ],
            'end_date' => [
                'required',
                'date',
                'after:start_date',
            ],
            'month' => [
                'required',
                'integer',
            ],
            'year' => [
                'required',
                'integer',
            ],
            'segment_volume' => [
                'required',
                'array',
                'min:1',
            ],
            'segment_volume.*' => [
                'numeric',
                'different:segment_value.*',
            ],
            'segment_value' => [
                'required',
                'array',
                'min:1',
            ],
            'segment_value.*' => [
                'numeric',
                'different:segment_volume.*',
            ],
            'deadline_date' => [
                'required',
                'array',
                'min:1',
                'max:1',
            ],
            'deadline_date.*' => [
                'required',
                'date',
            ],
            'quote_status_id' => [
                'required',
                'array',
                'min:1',
                'max:1',
            ],
            'quote_status_id.*' => [
                'required',
                'integer',
            ],
        ];

        $finalRulesSet = array_merge($rules, $simpleRules);

        return $finalRulesSet;
    }

    /**
     * @return array|string[]
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Batch :attribute is already taken.',
            'deadline_date.52.required' => 'Car Sold Deadline date is required.',
            'deadline_date.53.required' => 'Uncontactable Deadline date is required.',
        ];
    }
}
