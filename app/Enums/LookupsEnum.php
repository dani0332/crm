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
}
