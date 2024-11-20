<?php

namespace App\Http\Requests;

use App\Enums\quoteTypeCode;
use Illuminate\Foundation\Http\FormRequest;

class ValidateAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Change this if authorization logic is needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if ($this->input('modelType') == quoteTypeCode::Car) {
            $addressType = $this->input('addressObj.address_type');

            if (in_array($addressType, ['Home', 'Office'])) {
                // Merge nested fields into main request before validating
                $this->merge($this->input('addressObj'));

                return [
                    'address_type' => 'nullable|string|max:50',
                    'villa_apartment_office_no' => 'sometimes|required|string|max:20',
                    'floor_no' => 'sometimes|required|string|max:20',
                    'villa_building_name' => 'sometimes|required|string|max:100',
                    'area' => 'sometimes|required|string|max:100',
                    'city' => 'sometimes|required|string|max:100',
                    // `street_name` and `landmark` are optional
                ];
            }
        }

        return [];
    }
}
