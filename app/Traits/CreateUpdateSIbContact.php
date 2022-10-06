<?php

namespace App\Traits;

use App\Services\CreateUpdateContactService;

trait CreateUpdateSIbContact
{
    public function sendSibRequest($entity)
    {
        $data = [
            'customerName' => isset($entity->full_name) ? $entity->full_name : null,
            'advisorName' => isset($entity->advisor) ? $entity->advisor->name : null,
            'advisorEmail' => isset($entity->advisor) ? $entity->advisor->email : null,
            'advisorMobile' => isset($entity->advisor) ? $entity->advisor->mobile_no : null,
            'customerLastName' => isset($entity->last_name) ? $entity->last_name : null,
            'customerFirstName' => isset($entity->first_name) ? $entity->first_name : null,
            'leadStatus' => isset($entity->quoteStatus) ? $entity->quoteStatus->text : null,
            'cbdid' => isset($entity->code) ? $entity->code : null,
            'link' => config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$entity->uuid,
            'advisorLandline' => isset($entity->advisor) ? $entity->advisor->landline_no : null,
        ];

        return CreateUpdateContactService::contactCreateUpdate(config('constants.SIB_HEALTH_EBP_LIST_ID'), $entity->first_name, $entity->last_name, $entity->email, false, $data);
    }
}
