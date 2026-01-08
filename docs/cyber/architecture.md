# Cyber LOB Architecture

## System Architecture Overview

The Cyber LOB follows a layered architecture pattern with clear separation of concerns:

```
┌─────────────────────────────────────────────────────────┐
│                    Frontend Layer                       │
│  Vue.js 3.5 + Inertia.js + TailwindCSS                 │
│  - Form.vue (Lead Form)                                │
│  - Index.vue (Quote Listing)                           │
│  - Show.vue (Quote Details)                            │
└─────────────────────────────────────────────────────────┘
                          ↕
┌─────────────────────────────────────────────────────────┐
│                    Controller Layer                     │
│  CyberQuoteController                                   │
│  - Handles HTTP requests                                │
│  - Permission checks                                    │
│  - Response formatting                                  │
└─────────────────────────────────────────────────────────┘
                          ↕
┌─────────────────────────────────────────────────────────┐
│                    Service Layer                        │
│  CyberQuoteService                                      │
│  - Business logic                                      │
│  - Data manipulation                                   │
│  - Integration with external services                  │
└─────────────────────────────────────────────────────────┘
                          ↕
┌─────────────────────────────────────────────────────────┐
│                    Allocation Layer                    │
│  FetchAvailableAdvisorPipe                             │
│  - Advisor selection logic                             │
│  - Leave status checking                               │
│  - Test/Production mode handling                       │
└─────────────────────────────────────────────────────────┘
                          ↕
┌─────────────────────────────────────────────────────────┐
│                    Data Layer                           │
│  Models: CyberQuote                                     │
│  Database: MySQL                                        │
│  Application Storage: Configuration keys               │
└─────────────────────────────────────────────────────────┘
```

## Technology Stack

### Backend

- **Framework**: Laravel 10+
- **PHP Version**: 8.2+
- **Database**: MySQL
- **Architecture Pattern**: MVC with Service Layer

### Frontend

- **Framework**: Vue.js 3.5+
- **UI Framework**: Inertia.js
- **Styling**: TailwindCSS
- **Components**: @indielayer/ui

### Key Libraries

- **Validation**: Laravel Form Requests
- **Logging**: Custom LoggerService
- **Caching**: Laravel Cache

## Data Flow

### Quote Creation Flow

```
1. User fills form (Frontend)
   ↓
2. Form validation (Client-side + Server-side)
   ↓
3. CyberQuoteRequest (FormRequest) validation
   ↓
4. CyberQuoteService::create()
   ↓
5. External Capi API call (`/api/cyber/create`)
   ↓
6. External API creates PersonalQuote & CyberQuote records
   ↓
7. selfAssign() called (if advisorId set)
   ↓
8. Redirect to quote show page
   ↓
[Note: ILA triggered separately via allocation commands/events]
```

### Advisor Allocation Flow

```
1. Quote created/updated
   ↓
2. AllocationRequest created
   ↓
3. FetchAvailableAdvisorPipe::handle()
   ↓
4. Check if paid lead → Assign to Happiness User
   ↓
5. Else → Get advisors from CYBER_ADVISORS
   ↓
6. Check primary advisor leave status
   ↓
9. Return appropriate advisor emails
   ↓
10. Query advisors by status (ONLINE → OFFLINE → UNAVAILABLE)
   ↓
11. Assign first available advisor
```

## Key Components

### Controllers

- **CyberQuoteController**: Main controller handling CRUD operations
  - Location: `app/Http/Controllers/V2/CyberQuoteController.php`
  - Methods: `index`, `create`, `store`, `edit`, `update`, `show`

### Services

- **CyberQuoteService**: Core business logic
  - Location: `app/Services/Quotes/CyberQuoteService.php`
  - Extends: `BaseQuoteService`
  - Handles: Quote creation, updates, data retrieval

### Allocation Pipes

- **FetchAvailableAdvisorPipe**: Advisor allocation logic
  - Location: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php`
  - Extends: `BaseAllocationPipe`
  - Handles: Advisor selection, leave checking, test/production modes

### Models

- **PersonalQuote**: Main quote model (polymorphic)
- **CyberQuote**: Cyber-specific quote request data
- **User**: Advisor/user model for allocation

### Request Validation

- **CyberQuoteRequest**: Form request validation
  - Location: `app/Http/Requests/Cyber/CyberQuoteRequest.php`
  - Validates: Customer information, nationality, emirate

## Database Schema

### Core Tables

- `personal_quotes`: Main quote table (polymorphic)
- `cyber_quote_requests`: Cyber-specific quote data
- `users`: Advisor/user information
- `application_storages`: Configuration storage
- `lead_allocation`: Advisor allocation tracking

### Key Relationships

- `PersonalQuote` → `CyberQuote` (hasOne)
- `PersonalQuote` → `User` (belongsTo - advisor)
- `PersonalQuote` → `Nationality` (belongsTo)
- `PersonalQuote` → `Emirate` (belongsTo via CyberQuote)

## Configuration

### Application Storage Keys

- `CYBER_ADVISORS`: Advisor emails (comma-separated, first email is primary, rest are backups)

### Environment Variables

- Standard Laravel environment variables
- Database configuration
- Cache configuration

## Security

### Permissions

- `CYBER_QUOTES_CREATE`: Create quotes
- `CYBER_QUOTES_EDIT`: Edit quotes
- `CYBER_QUOTES_SHOW`: View quotes
- `CYBER_QUOTES_LIST`: List quotes
- `VIEW_ALL_LEADS`: View all leads (admin)

### Validation

- Server-side validation via Form Requests
- Client-side validation via Vue.js rules
- Input sanitization
- SQL injection protection (Eloquent ORM)

## Logging

All operations are logged using `LoggerService`:

- Quote creation/updates
- Advisor allocation
- Errors and warnings
- Allocation decisions

Log location: `storage/logs/laravel-YYYY-MM-DD.log`

## Error Handling

- Form validation errors displayed to user
- Allocation failures logged and handled gracefully
- Database errors caught and logged
- User-friendly error messages

## Performance Considerations

- Database queries optimized with eager loading
- Caching for lookup data (nationalities, emirates)
- Pagination for quote listings
- Indexed database columns for common queries

## Future Enhancements

- Enhanced plan selection
- Automated follow-up emails
- Payment integration improvements
- Advanced reporting
