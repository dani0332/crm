<?php

namespace App\Enums;

/**
 * Source of emirate of registration update for activity log tagging.
 * Used to distinguish Entity profile update vs AML screen in lead/quote updates.
 */
enum EmirateUpdateSourceEnum: string
{
    case ENTITY_PROFILE_UPDATE = 'entity_profile_update';
    case AML_SCREEN = 'aml_screen';
}
