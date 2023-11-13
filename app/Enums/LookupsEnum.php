<?php

namespace App\Enums;

enum LookupsEnum: string
{
    case JETSKI_MATERIALS = 'jetski-materials';
    case JETSKI_USES = 'jetski-uses';
    case PET_AGES = 'pet-ages';
    case PET_TYPES = 'pet-types';
    case CAR_LOST_REJECT_REASONS = 'car-lost-reject-reasons';
    case CAR_LOST_APPROVE_REASONS = 'car-lost-approve-reasons';
    case NEW_BUSINESS = 'new-business';
    case EXISTING_CUSTOMER_RENEWAL = 'ext-customer-renewal';
    case EXISTING_CUSTOMER_NEW_BUSINESS = 'ext-customer-new-business';
    case ENDORSEMENT = 'endorsement';
}
