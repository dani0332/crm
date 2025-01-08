<?php

namespace App\Http\Requests\PersonalQuotes;

use Illuminate\Foundation\Http\FormRequest;

class HomeQuoteRequest extends FormRequest
{
    const SOMETIMES_BOOLEAN = 'sometimes|boolean';
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
            'first_name' => 'required|between:1,20',
            'last_name' => 'required|between:1,50',
            'email' => 'required|email:rfc,dns|max:150',
            'mobile_no' => 'required|regex:/(0)[0-9]/|not_regex:/[a-z]/|min:7|max:20',
            'has_contents' => self::SOMETIMES_BOOLEAN,
            'has_building' => self::SOMETIMES_BOOLEAN,
            'has_personal_belongings' => self::SOMETIMES_BOOLEAN,
            // Conditionally required fields
            'contents_aed' => 'nullable|required_if:has_contents,true',
            'building_aed' => 'nullable|required_if:has_building,true|numeric|min:100000',
            'personal_belongings_aed' => 'nullable|required_if:has_personal_belongings,true',
            'sub_area_id' => 'required',
            'type_of_coverage_you_need' => 'required',
            'have_claimed_losses' => 'required|boolean',
            'ilivein_accommodation_type_id' => 'required|exists:home_accommodation_type,id',
            'iam_possesion_type_id' => 'required|exists:home_possession_type,id',
            'owner_occupancy_type_id' => 'required_if:iam_possesion_type_id,2',

            'addressObj' => 'required|array',
            'addressObj.villa_apartment_office_no' => 'required|string|max:50',
            'addressObj.villa_building_name' => 'required|string|max:100',
            'addressObj.street_name' => 'required|string|max:150',
        ];
    }

    /**
     * Custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'The first name is required.',
            'first_name.between' => 'The first name must be between 1 and 20 characters.',

            'last_name.required' => 'The last name is required.',
            'last_name.between' => 'The last name must be between 1 and 50 characters.',

            'email.required' => 'The email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.max' => 'The email address cannot exceed 150 characters.',

            'mobile_no.required' => 'The mobile number is required.',
            'mobile_no.regex' => 'The mobile number must start with 0 and contain only numbers.',
            'mobile_no.not_regex' => 'The mobile number cannot contain letters.',
            'mobile_no.min' => 'The mobile number must be at least 7 digits.',
            'mobile_no.max' => 'The mobile number cannot exceed 20 digits.',

            'has_contents.boolean' => 'The contents field must be true or false.',
            'has_building.boolean' => 'The building field must be true or false.',
            'has_personal_belongings.boolean' => 'The personal belongings field must be true or false.',

            'contents_aed.required_if' => 'The contents AED field is required when you have selected contents coverage.',
            'building_aed.required_if' => 'The building AED field is required when you have selected building coverage.',
            'building_aed.numeric' => 'The building AED field must be a valid number.',
            'building_aed.min' => 'The building AED field must be at least 100,000.',
            'personal_belongings_aed.required_if' => 'The personal belongings AED field is required when you have selected personal belongings coverage.',

            'sub_area_id.required' => 'The location area is required.',

            'type_of_coverage_you_need.required' => 'You must select the type of coverage you need.',

            'have_claimed_losses.required' => 'You must indicate if there are any claims.',
            'have_claimed_losses.boolean' => 'The claims field must be true or false.',

            'ilivein_accommodation_type_id.required' => 'You must select the type of property you live in.',
            'ilivein_accommodation_type_id.exists' => 'The selected property type is invalid.',

            'iam_possesion_type_id.required' => 'You must select your ownership status.',
            'iam_possesion_type_id.exists' => 'The selected ownership status is invalid.',

            'owner_occupancy_type_id.required_if' => 'The owner occupancy type is required if you are renting out your property.',

            'addressObj.required' => 'The address information is required.',
            'addressObj.array' => 'The address information must be an array.',
            'addressObj.villa_apartment_office_no.required' => 'The villa/apartment/office number is required.',
            'addressObj.villa_apartment_office_no.string' => 'The villa/apartment/office number must be a string.',
            'addressObj.villa_apartment_office_no.max' => 'The villa/apartment/office number cannot exceed 50 characters.',
            'addressObj.villa_building_name.required' => 'The building name is required.',
            'addressObj.villa_building_name.string' => 'The building name must be a string.',
            'addressObj.villa_building_name.max' => 'The building name cannot exceed 100 characters.',
            'addressObj.street_name.required' => 'The street name is required.',
            'addressObj.street_name.string' => 'The street name must be a string.',
            'addressObj.street_name.max' => 'The street name cannot exceed 150 characters.',
        ];
    }
}
