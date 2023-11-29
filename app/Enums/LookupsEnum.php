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
}
