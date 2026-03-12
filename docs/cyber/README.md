# Cyber Insurance LOB Documentation

## Overview

Cyber Insurance is a Line of Business (LOB) within the Blanka insurance platform that handles cyber insurance quotes, lead management, and advisor allocation. This documentation provides comprehensive information about the Cyber LOB architecture, modules, and implementation details.

## What is Cyber Insurance?

Cyber Insurance protects businesses and individuals from internet-based risks and cyber threats, including data breaches, ransomware attacks, and other cyber incidents. The Cyber LOB manages the complete quote lifecycle from lead capture to policy issuance.

## Key Features

- **Lead Form Management**: Customer information capture and validation
- **Instant Lead Allocation (ILA)**: Automated advisor assignment based on availability and leave status
- **Quote Management**: Create, update, and track cyber insurance quotes
- **Payment Processing**: Handle payment authorization and processing
- **Advisor Assignment**: Smart routing of leads to available advisors

## Table of Contents

### Core Documentation

- [Architecture](./architecture.md) - System architecture, tech stack, and flow diagrams

### Modules

- [Lead List](./modules/lead-list/README.md) - Lead list view and filtering
- [Lead Form](./modules/lead-form/README.md) - Lead form overview and implementation
  - [Form Fields](./modules/lead-form/form-fields.md) - Field definitions and validation rules
  - [Submission Flow](./modules/lead-form/submission-flow.md) - How form submission works
  - [API Endpoints](./modules/lead-form/api-endpoints.md) - Related API endpoints
- [Lead Details](./modules/lead-details/README.md) - Lead details page and management
- [ILA (Instant Lead Allocation)](./modules/ila/README.md) - Advisor allocation system
  - [Allocation Logic](./modules/ila/allocation-logic.md) - How advisors are selected
  - [Production Mode](./modules/ila/production-mode.md) - Allocation rules and behavior
  - [Daily Capacity](./modules/ila/daily-capacity.md) - Daily capacity management
  - [Business Requirements](./modules/ila/business-requirements.md) - FR documentation
  - [App Storage Keys](./modules/ila/app-storage-keys.md) - Storage key documentation
- [Available Plans](./modules/available-plans/README.md) - Plan display and selection
  - [Plan Fetching](./modules/available-plans/plan-fetching.md) - How plans are retrieved
  - [Plan Display](./modules/available-plans/plan-display.md) - Frontend display logic
  - [Plan Selection](./modules/available-plans/plan-selection.md) - Plan selection process
  - [API Integration](./modules/available-plans/api-integration.md) - KEN API integration
- [Permissions & Roles](./modules/permissions-roles.md) - Access control and permissions
- [Routes](./modules/routes.md) - All Cyber LOB routes and endpoints

## Tech Stack

- **Backend**: Laravel (PHP 8.2+)
- **Frontend**: Vue.js 3.5+ with Inertia.js
- **Database**: MySQL
- **Architecture**: MVC with Service Layer pattern

## Quick Start

### Creating a Cyber Quote

1. Navigate to Cyber Quotes → Create
2. Fill in customer details (first name, last name, email, mobile, DOB, nationality, emirate)
3. Submit the form
4. External Capi API creates the quote
5. Quote is created with status "New Lead"
6. ILA (Instant Lead Allocation) runs separately to assign advisor

### Key Files

- **Controller**: `app/Http/Controllers/V2/CyberQuoteController.php`
- **Service**: `app/Services/Quotes/CyberQuoteService.php`
- **Request Validation**: `app/Http/Requests/Cyber/CyberQuoteRequest.php`
- **Allocation Pipe**: `app/Pipes/Allocation/Cyber/FetchAvailableAdvisorPipe.php`
- **Frontend Form**: `resources/js/inertia/Pages/CyberQuote/Form.vue`

## Related Documentation

- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Documentation](https://vuejs.org/)
- [Inertia.js Documentation](https://inertiajs.com/)

## Support

For questions or issues related to Cyber LOB, please contact the development team.
