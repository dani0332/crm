<?php

namespace App\Services;

use App\Enums\quoteBusinessTypeCode;
use App\Enums\quoteTypeCode;
use App\Events\PaymentNotifications;
use App\Traits\GenericQueriesAllLobs;

class NotificationService extends BaseService
{
    use GenericQueriesAllLobs;

    public function paymentStatusUpdate($quoteType, $quoteId)
    {
        if (is_numeric($quoteType)) {
            return response()->json(['message' => 'Quote Type Not Valid'], 403);
        }
        $model = null;

        if (isset($quoteType) && isset($quoteId)) {
            $model = $this->getQuoteObjectBy($quoteType, $quoteId, 'uuid');

        }
        if (! $model) {
            return response()->json(['message' => 'Quote Not Found'], 403);
        }
        if ($model->advisor_id === null) {
            return response()->json(['message' => 'No Advisor Assign to this Lead'], 403);
        }

        $url = url('/');
        if ($quoteType == quoteTypeCode::Business) {
            if ($model->business_type_of_insurance_id == quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical)) {
                $url .= "/medical/amt/$model->uuid";
            } else {
                $url .= "/quotes/business/$model->uuid";
            }
        } else {
            $url .= '/quotes/'.strtolower($quoteType).'/'.$model->uuid;
        }

        if (checkPersonalQuotes(ucwords($quoteType))) {
            $url = '/personal-quotes/'.strtolower($quoteType).'/'.$model->uuid;

        }

        info('Payment Notification Event Trigger'.$model->uuid);
        $quoteType = strtoupper(substr(trim($quoteType), 0, 3));
        event(new PaymentNotifications($model, $url, $quoteType));

        return response()->json(['message' => 'Payment notification successfully send to advisor!'], 200);
    }

}
