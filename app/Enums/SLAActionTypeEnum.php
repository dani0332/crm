<?php

declare(strict_types=1);

namespace App\Enums;

enum SLAActionTypeEnum: string
{
    use Enumable;

    case STATUS_UPDATED = 'status_updated';
    case PEC_TAG_REMOVED = 'pec_tag_removed';
    case CUSTOMER_PROFILE_EDIT = 'customer_profile_edit';
    case LEAD_EDIT = 'lead_edit';
    case MEMBER_DETAILS_EDIT = 'member_details_edit';
    case MEMBER_DETAILS_ADD = 'member_details_add';
    case MEMBER_DETAILS_DELETE = 'member_details_delete';
    case ADDITIONAL_CONTACTS_EDIT = 'additional_contacts_edit';
    case ADDITIONAL_CONTACTS_ADD = 'additional_contacts_add';
    case ADDITIONAL_CONTACTS_PRIMARY_UPDATE = 'additional_contacts_primary_update';
    case AVAILABLE_PLAN_SELECTED = 'available_plan_selected';
    case DOCUMENTS_UPLOAD = 'documents_uploaded';

    public function label()
    {
        return match ($this) {
            self::STATUS_UPDATED => 'Status Updated',
            self::PEC_TAG_REMOVED => 'PEC Tag Removed (Customer Re-contacted)',
            self::CUSTOMER_PROFILE_EDIT => 'Customer Profile Updated',
            self::LEAD_EDIT => 'Lead Updated',
            self::MEMBER_DETAILS_EDIT => 'Member Details Updated',
            self::MEMBER_DETAILS_ADD => 'Member Details Added',
            self::MEMBER_DETAILS_DELETE => 'Member Details Deleted',
            self::ADDITIONAL_CONTACTS_EDIT => 'Additional Contacts Updated',
            self::ADDITIONAL_CONTACTS_ADD => 'Additional Contacts Added',
            self::AVAILABLE_PLAN_SELECTED => 'Available Plan Selected',
            self::ADDITIONAL_CONTACTS_PRIMARY_UPDATE => 'Additional Contacts Primary Updated',
            self::DOCUMENTS_UPLOAD => 'Documents Uploaded',
        };
    }
}
