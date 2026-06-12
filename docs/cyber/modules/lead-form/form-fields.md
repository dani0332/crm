# Form Fields Documentation

## Overview

This document provides detailed information about each field in the Cyber Insurance lead form, including validation rules, data types, and constraints.

## Field Definitions

### 1. First Name

**Field Name**: `first_name`  
**Type**: Text Input  
**Required**: Yes  
**Max Length**: 20 characters  
**Validation Rules**:

- Required
- Between 1-20 characters
- Regex: `/^[a-zA-Z\s\-]+$/` (letters, spaces, and hyphens only)

**Backend Validation**:

```php
'first_name' => 'required|between:1,20|regex:/^[a-zA-Z\s\-]+$/'
```

**Frontend Component**:

```vue
<x-input
  v-model="quoteForm.first_name"
  type="text"
  label="First Name"
  required
  :rules="[isRequired, isValidName]"
  class="w-full"
  maxLength="20"
  :error="quoteForm.errors.first_name"
/>
```

**Code Reference**: `resources/js/inertia/Pages/CyberQuote/Form.vue:83-92`

**Error Messages**:

- Required: "The first name field is required."
- Regex: "The first name may only contain letters, spaces, and hyphens."

**Location**:

- Backend: `app/Http/Requests/Cyber/CyberQuoteRequest.php:31`
- Frontend: `resources/js/inertia/Pages/CyberQuote/Form.vue:83-92`

---

### 2. Last Name

**Field Name**: `last_name`  
**Type**: Text Input  
**Required**: Yes  
**Max Length**: 50 characters  
**Validation Rules**:

- Required
- Between 1-50 characters
- Regex: `/^[a-zA-Z\s\-]+$/` (letters, spaces, and hyphens only)

**Backend Validation**:

```php
'last_name' => 'required|between:1,50|regex:/^[a-zA-Z\s\-]+$/'
```

**Frontend Component**:

```vue
<x-input
  v-model="quoteForm.last_name"
  type="text"
  label="Last Name"
  required
  maxLength="50"
  :rules="[isRequired, isValidName]"
  class="w-full"
  :error="quoteForm.errors.last_name"
/>
```

**Code Reference**: `resources/js/inertia/Pages/CyberQuote/Form.vue:93-102`

**Error Messages**:

- Required: "The last name field is required."
- Regex: "The last name may only contain letters, spaces, and hyphens."

**Location**:

- Backend: `app/Http/Requests/Cyber/CyberQuoteRequest.php:32`
- Frontend: `resources/js/inertia/Pages/CyberQuote/Form.vue:93-102`

---

### 3. Email

**Field Name**: `email`  
**Type**: Email Input  
**Required**: Yes  
**Editable**: No (disabled in edit mode)  
**Validation Rules**:

- Required
- Valid email format

**Backend Validation**:

```php
'email' => 'required|email'
```

**Frontend Component**:

```vue
<x-input
  v-model="quoteForm.email"
  type="email"
  label="Email"
  required
  :disabled="editMode"
  :rules="[isRequired, isEmail]"
/>
```

**Error Messages**:

- Required: "The email field is required."
- Email: "The email must be a valid email address."

**Location**:

- Backend: `app/Http/Requests/Cyber/CyberQuoteRequest.php:33`
- Frontend: `resources/js/inertia/Pages/CyberQuote/Form.vue:103-112`

**Note**: Email is immutable once quote is created. This prevents data inconsistency.

---

### 4. Mobile Number

**Field Name**: `mobile_no`  
**Type**: Tel Input  
**Required**: Yes  
**Editable**: No (disabled in edit mode)  
**Validation Rules**:

- Required
- String format
- Mobile number format validation (client-side only in create mode)

**Backend Validation**:

```php
'mobile_no' => 'required|string'
```

**Frontend Component**:

```vue
<x-input
  v-model="quoteForm.mobile_no"
  type="tel"
  label="Phone Number"
  required
  :disabled="editMode"
  :rules="[isRequired, ...(editMode ? [] : [isMobileNo])]"
/>
```

**Error Messages**:

- Required: "The mobile no field is required."

**Location**:

- Backend: `app/Http/Requests/Cyber/CyberQuoteRequest.php:34`
- Frontend: `resources/js/inertia/Pages/CyberQuote/Form.vue:113-122`

**Note**: Mobile number is immutable once quote is created.

---

### 5. Date of Birth

**Field Name**: `dob`  
**Type**: Date Picker  
**Required**: Yes  
**Format**: YYYY-MM-DD  
**Validation Rules**:

- Required
- Valid date format

**Backend Validation**:

```php
'dob' => 'required|date'
```

**Frontend Component**:

```vue
<DatePicker
  v-model="quoteForm.dob"
  label="Date of Birth"
  required
  :rules="[isRequired]"
/>
```

**Error Messages**:

- Required: "The dob field is required."
- Date: "The dob must be a valid date."

**Location**:

- Backend: `app/Http/Requests/Cyber/CyberQuoteRequest.php:35`
- Frontend: `resources/js/inertia/Pages/CyberQuote/Form.vue:124-130`

**Data Formatting**:

- Display: Formatted using `useDateFormat(date, 'YYYY-MM-DD')`
- Storage: Stored as date in database

---

### 6. Nationality

**Field Name**: `nationality_id`  
**Type**: Select Dropdown  
**Required**: Yes  
**Data Source**: Database lookup (`nationalities` table)  
**Validation Rules**:

- Required
- Must exist in `nationalities` table

**FR Requirement**:

- Dropdown values taken from nationality mapping sheet
- Backend stores both country name and mapped country code
- Country code mapping per business requirements

**Backend Validation**:

```php
'nationality_id' => ['required', Rule::exists(Nationality::class, 'id')]
```

**Frontend Component**:

```vue
<x-select
  v-model="quoteForm.nationality_id"
  :options="nationalities"
  class="w-full"
  :error="quoteForm.errors.nationality_id"
  :rules="[isRequired]"
  label="Nationality"
  filterable
  placeholder="Search by Nationality"
  required
/>
```

**Code Reference**: `resources/js/inertia/Pages/CyberQuote/Form.vue:134-144`

**Data Preparation**:

```javascript
const nationalities = computed(() => {
  return props.lookUpData.nationality.map(item => ({
    value: item.id,
    label: item.text,
  }));
});
```

**Code Reference**: `resources/js/inertia/Pages/CyberQuote/Form.vue:12-17`

**Features**:

- Filterable dropdown (searchable)
- Placeholder: "Search by Nationality"

**Error Messages**:

- Required: "The nationality id field is required."
- Exists: "The selected nationality id is invalid."

**Location**:

- Backend: `app/Http/Requests/Cyber/CyberQuoteRequest.php:36`
- Frontend: `resources/js/inertia/Pages/CyberQuote/Form.vue:131-139`

**Lookup Data**: Retrieved via `CyberQuoteService::getFormOptions()`

---

### 7. Emirate of Registration

**Field Name**: `emirate_of_registration_id`  
**Type**: Select Dropdown  
**Required**: Yes  
**Data Source**: Database lookup (`emirates` table)  
**Validation Rules**:

- Required
- Must exist in `emirates` table

**FR Requirement**:

- Dropdown with all emirates: Dubai, Ras al Khaimah, Abu Dhabi, Sharjah, Ajman, Umm Al Quwain, Fujairah
- Also referred to as "Emirate of residence" in FR

**Backend Validation**:

```php
'emirate_of_registration_id' => ['required', Rule::exists(Emirate::class, 'id')]
```

**Frontend Component**:

```vue
<x-select
  v-model="quoteForm.emirate_of_registration_id"
  :options="emiratesOfRegistration"
  class="w-full"
  :error="quoteForm.errors.emirate_of_registration_id"
  :rules="[isRequired]"
  label="Emirate of Registration"
  filterable
  placeholder="Search by Emirate of Registration"
  required
/>
```

**Code Reference**: `resources/js/inertia/Pages/CyberQuote/Form.vue:146-156`

**Data Preparation**:

```javascript
const emiratesOfRegistration = computed(() => {
  return props.lookUpData.emiratesOfRegistration.map(item => ({
    value: item.id,
    label: item.text,
  }));
});
```

**Code Reference**: `resources/js/inertia/Pages/CyberQuote/Form.vue:19-24`

**Features**:

- Filterable dropdown (searchable)
- Placeholder: "Search by Emirate of Registration"

**Error Messages**:

- Required: "The emirate of registration id field is required."
- Exists: "The selected emirate of registration id is invalid."

**Location**:

- Backend: `app/Http/Requests/Cyber/CyberQuoteRequest.php:37`
- Frontend: `resources/js/inertia/Pages/CyberQuote/Form.vue:140-148`

**Lookup Data**: Retrieved via `CyberQuoteService::getFormOptions()`

---

## Form Data Structure

### Request Payload (Create)

```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john.doe@example.com",
  "mobile_no": "+971501234567",
  "dob": "1990-01-15",
  "nationality_id": 56,
  "emirate_of_registration_id": 2
}
```

### Form Object (Frontend)

```javascript
{
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  dob: '',
  nationality_id: '',
  emirate_of_registration_id: ''
}
```

## Validation Flow

1. **Client-side Validation** (Real-time)

   - Triggered on field blur/change
   - Uses Vue.js validation rules
   - Immediate feedback to user

2. **Form Submission**

   - All fields validated before submission
   - Form submission blocked if validation fails

3. **Server-side Validation**
   - Laravel Form Request validation
   - Database existence checks
   - Returns validation errors if invalid

## Related Files

- **Validation Rules**: `app/Http/Requests/Cyber/CyberQuoteRequest.php`
- **Form Component**: `resources/js/inertia/Pages/CyberQuote/Form.vue`
- **Service**: `app/Services/Quotes/CyberQuoteService.php`
- **Model**: `app/Models/CyberQuote.php`
