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
    case TRANSACTION_TYPES = 'transaction-types';
    case NEW_BUSINESS = 'newBusiness';
    case EXT_CUSTOMER_RENWAL = 'extCustomerRenewal';
    case EXT_CUSTOMER_NEW_BUSINESS = 'extCustomerNewBusiness';
    case ENDORSEMENT = 'endorsement';
}
