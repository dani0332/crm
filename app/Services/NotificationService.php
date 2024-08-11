<?php

namespace App\Services;

use App\Enums\quoteBusinessTypeCode;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeShortCode;
use App\Events\PaymentNotifications;
use App\Traits\GenericQueriesAllLobs;

class NotificationService extends BaseService
{
    use GenericQueriesAllLobs;

    public function paymentStatusUpdate($request)
    {
        info('Payment Notification Function Call');

        if (is_numeric($request->quoteType)) {
            return response()->json(['message' => 'Quote Type Not Valid'], 403);
        }
        $model = $this->getModelObject(strtolower($request->quoteType));
        $url = url('/');

        if (is_numeric($request->quoteId)) {
            $model = $model::find($request->quoteId);
        } else {
            $model = $model::where('uuid', $request->quoteId)->first();
        }

        if ($request->quoteType == quoteTypeCode::Business) {
            if ($model->business_type_of_insurance_id == quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical)) {
                $url .= "/medical/amt/$model->uuid";
            } else {
                $url .= "/quotes/business/$model->uuid";
            }
        } else {
            $url .= '/quotes/'.strtolower($request->quoteType).'/'.$model->uuid;
        }

        if (checkPersonalQuotes(ucwords($request->quoteType))) {
            $url = '/personal-quotes/'.strtolower($request->quoteType).'/'.$model->uuid;

        }
        if ($model->advisor_id === null) {
            return response()->json(['message' => 'No Advisor Assign to this Lead.'], 403);
        }

        info('Payment Notification Event Trigger');
        $quoteType = strtoupper(substr(trim($request->quoteType), 0, 3));
        event(new PaymentNotifications($model, $url, $quoteType));
    }
}
