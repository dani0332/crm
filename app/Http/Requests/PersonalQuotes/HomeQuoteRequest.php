<?php

namespace App\Http\Requests\PersonalQuotes;

use App\Enums\GenericRequestEnum;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|between:1,20|regex:/^[a-zA-Z\s\-]+$/',
            'last_name' => 'required|between:1,50|regex:/^[a-zA-Z\s\-]+$/',
            'email' => 'required|email:rfc,dns|max:150',
            'mobile_no' => 'required|min:7|max:20',
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
            'ilivein_accommodation_type_id' => 'required',
            'iam_possesion_type_id' => 'required',
            'owner_occupancy_type_id' => 'required_if:iam_possesion_type_id,2',

            'addressObj' => 'required|array',
            'addressObj.villa_apartment_office_no' => 'required|string|max:50',
            'addressObj.villa_building_name' => 'required|string|max:100',
            'addressObj.street_name' => 'required|string|max:150',
            'dob' => 'nullable|date_format:Y-m-d|before:today',
            'nationality_id' => 'nullable|exists:nationality,id',
            'gender' => 'nullable|string|in:'.GenericRequestEnum::MALE_SINGLE.','.GenericRequestEnum::FEMALE.'',
            'company_name' => 'nullable|string|max:200',
            'company_address' => 'nullable|string',
            // Sub-source validation rules
            'sub_source_id' => 'nullable|integer|exists:lookups,id',
            'sub_source_options_id' => 'nullable|integer|exists:lookups,id',
            'notes' => 'nullable|string|max:1000',
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
            'first_name.regex' => 'The first name must contain only letters (A-Z, a-z), spaces, and hyphens (-). No numbers or other special characters allowed.',

            'last_name.required' => 'The last name is required.',
            'last_name.between' => 'The last name must be between 1 and 50 characters.',
            'last_name.regex' => 'The last name must contain only letters (A-Z, a-z), spaces, and hyphens (-). No numbers or other special characters allowed.',

            'email.required' => 'The email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.max' => 'The email address cannot exceed 150 characters.',

            'mobile_no.required' => 'The mobile number is required.',
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

            'iam_possesion_type_id.required' => 'You must select your ownership status.',

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

            'dob.date_format' => 'The date of birth must be in the format YYYY-MM-DD.',
            'dob.before' => 'The date of birth must be before today.',

            'nationality_id.exists' => 'The selected nationality is invalid.',

            'gender.string' => 'The gender must be a valid string.',
            'gender.in' => 'The selected gender is invalid. Please choose a valid option.',

            'company_name.string' => 'The company name must be a valid string.',
            'company_name.max' => 'The company name cannot exceed 200 characters.',

            'company_address.string' => 'The company address must be a valid string.',

        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'dob' => isset($this->dob) ? Carbon::parse($this->dob)->format('Y-m-d') : null,
        ]);
    }
}
