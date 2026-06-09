<?php

namespace App\Enums;

enum LookupsEnum: string
{
    case JETSKI_MATERIALS = 'jetski-materials';
    case JETSKI_USES = 'jetski-uses';
    case PET_AGES = 'pet-ages';
    case PET_TYPES = 'pet-types';
    case MEMBER_RELATION = 'member-relation';
    case UBO_RELATION = 'ubo-relation';
    case COMPANY_TYPE = 'company-type';
    case CAR_LOST_REJECT_REASONS = 'car-lost-reject-reasons';
    case CAR_LOST_APPROVE_REASONS = 'car-lost-approve-reasons';
    case TRANSACTION_TYPES = 'transaction-types';
    case NEW_BUSINESS = 'newBusiness';
    case EXT_CUSTOMER_RENWAL = 'extCustomerRenewal';
    case EXT_CUSTOMER_NEW_BUSINESS = 'extCustomerNewBusiness';
    case ENDORSEMENT = 'endorsement';
    case ENTITY_TYPE = 'entity-type';
    case PARENT_ENTITY = 'Parent';
    case SUB_ENTITY = 'SubEntity';
    case RESIDENT_STATUS = 'resident-status';
    case DOCUMENT_ID_TYPE = 'id-type';
    case MODE_OF_CONTACT = 'mode-of-contact';
    case LEGAL_STRUCTURE = 'legal-structure';
    case ISSUANCE_PLACE = 'issuance-place';
    case ENTITY_DOCUMENT_TYPE = 'entity-document-type';
    case ISSUING_AUTHORITY = 'issuing-authority';
    case EMPLOYMENT_SECTOR = 'employment-sector';
    case COMPANY_POSITION = 'company-position';
    case MODE_OF_DELIVERY = 'mode-of-delivery';
    case DELETED_MODE_OF_DELIVERY = 'mode-of-delivery-deleted';
    case PROFESSIONAL_TITLE = 'professional-title';
    case PAYMENT_COLLECTION_TYPE = 'payment_collection_type';
    case PAYMENT_FREQUENCY_TYPE = 'payment_frequency_type';
    case PAYMENT_DECLINE_REASON = 'payment_decline_reason';
    case PAYMENT_CREDIT_APPROVAL_REASON = 'payment_credit_approval_reason';
    case PAYMENT_DISCOUNT_TYPE = 'payment_discount_type';
    case SYSTEM_ADJUSTED_DISCOUNT = 'system_adjusted_discount';
    case PAYMENT_DISCOUNT_REASON = 'payment_discount_reason';
    case SEND_UPDATE_CODE = 'send-update-code';
    case BUSINESS_TYPE_OF_CUSTOMER = 'business-type-of-customer';
    case SEND_UPDATE_CANCEL_OPTIONS = 'send-update-cancel-options';
    case LIFE_PLAN_SUB_TYPE = 'plan-sub-type';
    case POSSESSION_TYPE = 'possession-type';
    case ACCOMMODATION_TYPE = 'accommodation-type';
    case OWNER_OCCUPANCY_TYPE = 'owner-occupancy-type';
    case COVERAGE_TYPE = 'coverage-type';
    case COVERAGE_POSSESSION_TYPE = 'coverage-possession-type';

    case SAVINGS_PURPOSE = 'savings_purpose';
    case INVESTMENT_TYPE = 'investment_type';
    case SAVINGS_TENURE = 'tenure';
    case RTA_TRANSACTION_TYPE = 'rta-transaction-type';
    case PLATE_CODE = 'plate-code';
    case RTA_PLATE_CATEGORY = 'rta-plate-category';
    case VEHICLE_COLOR = 'vehicle-color';
    case BANK_NAME = 'bank-name';
    case ANNUAL_MILEAGE_ESTIMATE = 'annual-mileage-estimate';
    case NATIONALITY_LIST = 'nationality-list';
    case DRIVING_EXPERIENCE = 'driving-experience';
    case RM_CATEGORY = 'rm-category';
    case SUB_SOURCE = 'sub-source';
    case SUB_SOURCE_OPTION = 'sub-source-option';

    case CYBER_COVERAGE = 'cyber-coverage';
    case HEALTH_INSURE_OPTIONS = 'health_insure_options';
    case POLICY_HOLDER_OPTIONS = 'policy_holder_options';
    case POLICY_HOLDER_CATEGORY = 'policy_holder_category';
    case GENDER = 'gender';
    case HEALTH_MEMBER_RELATION = 'health-member-relation';
    case DOMESTIC_WORKER_RELATION = 'domestic-worker-relation';
    case CORPLINE_RENEWAL_STATUS = 'corpline-renewal-status';
}
