<?php

namespace App\Http\Requests;

use App\Enums\quoteTypeCode;
use Illuminate\Foundation\Http\FormRequest;

class CustomerAddressRequest extends FormRequest
{
    const STRING_REQUIRED_MAX_100 = 'sometimes|required|string|max:100';
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
                    'villa_building_name' => self::STRING_REQUIRED_MAX_100,
                    'area' => self::STRING_REQUIRED_MAX_100,
                    'city' => self::STRING_REQUIRED_MAX_100,
                    // `street_name` and `landmark` are optional
                ];
            }
        }

        return [];
    }
}
