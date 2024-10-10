<?php

namespace App\Services;

use App\Models\EmailActivity;

class EmailActivityService extends BaseService
{
    public function addEmailActivity($getResponse, $isEmailSent, $customerEmail)
    {
        if ($getResponse && $customerEmail) {
            $newEmailActivity = new EmailActivity;
            $newEmailActivity->api_response = $getResponse;
            $newEmailActivity->successful = $isEmailSent;
            $newEmailActivity->email = $customerEmail;
            $newEmailActivity->save();

            return $newEmailActivity->id;
        }
    }
}
