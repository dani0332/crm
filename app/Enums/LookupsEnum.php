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
    case ENTITY_TYPE = 'entity-type';
}
