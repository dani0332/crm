# Submission Flow Documentation

## Overview

This document describes the complete flow of form submission in the Cyber Insurance lead form, from user interaction to database persistence and advisor assignment.

## Flow Diagram

```
User Fills Form
    ↓
Client-side Validation
    ↓
Form Submit Clicked
    ↓
Client-side Validation Check
    ↓
[Invalid] → Show Errors → User Corrects
    ↓
[Valid]
    ↓
HTTP Request Sent
    ↓
Server-side Validation (CyberQuoteRequest)
    ↓
[Invalid] → Return Validation Errors → Display to User
    ↓
[Valid]
    ↓
CyberQuoteService::create() or update()
    ↓
[Create] → External Capi API Call → Creates PersonalQuote & CyberQuote
    ↓
[Create] → selfAssign() (if advisorId set)
    ↓
[Update] → Update PersonalQuote & CyberQuote directly
    ↓
Redirect to Quote Show Page
    ↓
[Note: ILA triggered separately, not during form submission]
```

## Detailed Flow

### 1. User Interaction

**Location**: `resources/js/inertia/Pages/CyberQuote/Form.vue`

User fills in the form fields:

- First Name
- Last Name
- Email
- Mobile Number
- Date of Birth
- Nationality (dropdown)
- Emirate of Registration (dropdown)

**Code Reference**:

```vue
const quoteForm = useForm({ first_name: props.quote?.first_name || '',
last_name: props.quote?.last_name || '', email: props.quote?.email || '',
mobile_no: props.quote?.mobile_no || '', dob: props.quote?.dob ?
dateFormat(props.quote?.dob) : '', nationality_id: props.quote?.nationality_id
|| '', emirate_of_registration_id:
props.quote?.cyber_quote?.emirate_of_registration_id || '', });
```

### 2. Client-side Validation

**Location**: `resources/js/inertia/Pages/CyberQuote/Form.vue:45-59`

Validation occurs:

- On field blur (real-time)
- On form submission (before HTTP request)

**Validation Rules**:

- `isRequired`: Field must not be empty
- `isEmail`: Valid email format
- `isMobileNo`: Valid mobile number format (create mode only)
- `isValidName`: Name format validation (letters, spaces, hyphens)

**Code Reference**:

```javascript
function onSubmit(isValid) {
  if (isValid) {
    quoteForm.clearErrors();
    let method = editMode.value ? 'put' : 'post';
    const url = editMode.value
      ? route('cyber-quotes-update', props.quote.uuid)
      : route('cyber-quotes-store');

**Code Reference**: `resources/js/inertia/Pages/CyberQuote/Form.vue:45-58`

**Route URLs**:
- Create: `POST /personal-quotes/cyber` (route name: `cyber-quotes-store`)
- Update: `PUT /personal-quotes/cyber/{uuid}` (route name: `cyber-quotes-update`)

    quoteForm.submit(method, url, {
      onError: errors => {
        console.log(quoteForm.setError(errors));
      },
    });
  }
}
```

### 3. HTTP Request

**Routes**:

- **Create**: `POST /personal-quotes/cyber` → `CyberQuoteController@store` (route name: `cyber-quotes-store`)
- **Update**: `PUT /personal-quotes/cyber/{uuid}` → `CyberQuoteController@update` (route name: `cyber-quotes-update`)

**Route Definition**: `routes/web.php:305-307`

```php
Route::prefix('personal-quotes')->group(function () {
    Route::resource('/cyber', CyberQuoteController::class)
        ->names(generateRouteNames('cyber-quotes'));
});
```

### 4. Server-side Validation

**Location**: `app/Http/Requests/Cyber/CyberQuoteRequest.php`

**Validation Rules**:

```php
public function rules(): array
{
    return [
        'first_name' => 'required|between:1,20|regex:/^[a-zA-Z\s\-]+$/',
        'last_name' => 'required|between:1,50|regex:/^[a-zA-Z\s\-]+$/',
        'email' => 'required|email',
        'mobile_no' => 'required|string',
        'dob' => 'required|date',
        'nationality_id' => ['required', Rule::exists(Nationality::class, 'id')],
        'emirate_of_registration_id' => ['required', Rule::exists(Emirate::class, 'id')],
    ];
}
```

**Permission Check**:

```php
public function authorize(): bool
{
    return $this->user()->can(PermissionsEnum::CYBER_QUOTES_CREATE)
        || $this->user()->can(PermissionsEnum::CYBER_QUOTES_EDIT)
        || $this->user()->can(PermissionsEnum::VIEW_ALL_LEADS);
}
```

**If Validation Fails**:

- Returns 422 Unprocessable Entity
- Errors sent back to frontend
- Form errors displayed inline

### 5. Service Layer Processing

**Location**: `app/Services/Quotes/CyberQuoteService.php`

#### Create Flow (`store` method)

**Controller**:

```php
public function store(CyberQuoteRequest $request)
{
    $response = $this->cyberQuoteService->create($request->validated());

    if (! empty($response->errors) || ! empty($response->msg)) {
        vAbort($response->msg);
    }

    return redirect(route('cyber-quotes-show', $response->quoteUID))

**Route URL**: `GET /personal-quotes/cyber/{uuid}` (route name: `cyber-quotes-show`)
        ->with('message', 'Quote is created successfully.');
}
```

**Service Method** (`create`):

1. Prepares data for external API call
2. Calls external Capi API: `Capi::request('/api/cyber/create', 'post', $data)`
3. External API creates `PersonalQuote` and `CyberQuote` records
4. If `quoteUID` is returned, calls `selfAssign()` for self-assignment handling
5. Returns API response with `quoteUID`

#### Update Flow (`update` method)

**Controller**:

```php
public function update(CyberQuoteRequest $request, $uuid)
{
    $this->cyberQuoteService->update($uuid, $request->validated());

    return redirect(route('cyber-quotes-show', $uuid))

**Route URL**: `GET /personal-quotes/cyber/{uuid}` (route name: `cyber-quotes-show`)
        ->with('message', 'Quote is updated successfully.');
}
```

**Service Method** (`update`):

1. Finds existing quote by UUID
2. Updates `PersonalQuote` record
3. Updates `CyberQuote` record
4. Returns success

### 6. External API Call

**Location**: `app/Services/Quotes/CyberQuoteService.php:272`

**API Endpoint**: `/api/cyber/create` (External Capi API)

**Request Data**:

```php
[
    'firstName' => $data['first_name'],
    'lastName' => $data['last_name'],
    'email' => $data['email'],
    'mobileNo' => $data['mobile_no'],
    'dob' => $data['dob'],
    'nationalityId' => (int) $data['nationality_id'],
    'emirateOfRegistrationId' => (int) $data['emirate_of_registration_id'],
    'quoteTypeId' => (int) $this->quoteType->id(),
    'lang' => 'EN',
    'device' => 'DESKTOP',
    'source' => $sourceName,
    'referenceUrl' => $appUrl,
    'advisorId' => (! $this->hasRole(Auth::user(), RolesEnum::Admin)) ? Auth::id() : null,
]
```

**Response**: Returns object with `quoteUID` if successful

**Note**: The external API creates the `PersonalQuote` and `CyberQuote` records in the database.

### 7. Self-Assignment Handling

**Location**: `app/Services/Quotes/CyberQuoteService.php:275`

**Method**: `selfAssign(QuoteTypes::CYBER, $response->quoteUID, true)`

**Purpose**: Handles self-assignment if `advisorId` was set in the API request (non-admin users)

**Note**: This does NOT trigger ILA. ILA is triggered separately via allocation commands or events.

### 8. Advisor Allocation (ILA)

**Location**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php`

ILA is triggered separately (not automatically during quote creation). When ILA runs:

1. **Automation Flow**: If AWNI automation completed/failed → Assigns CHS advisor
2. **Normal Flow**: Fetches available advisor based on:
   - Advisor emails from CYBER_ADVISORS app storage
   - Advisor leave status
   - Advisor availability (ONLINE → OFFLINE → UNAVAILABLE)
   - SIC advisor requested flag

**Code Reference**:

```php
public function handle(AllocationRequest $request, Closure $next)
{
    // CHS Advisor assignment for automation-completed/failed leads
    if ($this->allocationRequest->get('isCHSAdvisor')) {
        $advisor = $this->findAvailableAdvisor(teamId: null);
        // findAvailableAdvisor returns CHS advisors when isCHSAdvisor flag is set
        $this->allocationRequest->setAdvisor($advisor);
        return $next($request);
    }

    // Normal SIC advisor allocation
    $advisor = $this->findAvailableAdvisor(teamId: null);
    // Find and assign available advisor based on test/production mode
    $this->allocationRequest->setAdvisor($advisor);
    return $next($request);
}
```

### 9. Response and Redirect

**Success Response**:

- HTTP 302 Redirect
- Redirects to quote show page: `/personal-quotes/cyber/{uuid}` (route name: `cyber-quotes-show`)
- Success message: "Quote is created successfully." or "Quote is updated successfully."

**Error Response**:

- HTTP 422 (Validation errors)
- Errors returned as JSON
- Displayed inline in form

## Error Handling

### Client-side Errors

- Displayed below each field
- Real-time feedback
- Prevents form submission if invalid

### Server-side Errors

- Validation errors: Displayed inline
- Server errors: Shown as alert message
- Form state preserved on errors

### Allocation Errors

- Logged via `LoggerService`
- Allocation failure doesn't block quote creation
- Quote created without advisor (can be assigned manually)

## Success Scenarios

### Create Success

1. Form submitted
2. Validation passes
3. Quote created
4. Advisor assigned
5. Redirect to quote show page
6. Success message displayed

### Update Success

1. Form submitted
2. Validation passes
3. Quote updated
4. Redirect to quote show page
5. Success message displayed

## Related Files

- **Controller**: `app/Http/Controllers/V2/CyberQuoteController.php`
- **Request**: `app/Http/Requests/Cyber/CyberQuoteRequest.php`
- **Service**: `app/Services/Quotes/CyberQuoteService.php`
- **Form Component**: `resources/js/inertia/Pages/CyberQuote/Form.vue`
- **Allocation Pipe**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php`

## Logging

All operations are logged:

- Quote creation/update
- Validation failures
- Advisor allocation
- Errors and warnings

**Log Location**: `storage/logs/laravel-YYYY-MM-DD.log`

## Performance Considerations

- Database transactions for data consistency
- Eager loading for relationships
- Caching for lookup data
- Optimized queries
