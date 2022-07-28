<?php

namespace App\Traits;

use App\Models\Customer;
use App\Models\CustomerAdditionalInfo as CustomerAdditionalInfoModel;

trait CustomerAdditionalInfo
{
    public function createUpdateCustomerInfo($request, $email, $quoteUID, $quoteType)
    {
        if ($request->has('phones') && $request->has('emails') && $quoteUID && $quoteType) {
            $phones = $request->phones;
            $emails = $request->emails;
            $nameSpace = '\\App\\Models\\';
            $modelType = $nameSpace.$quoteType;
            $customer = Customer::whereEmail($email)->first();
            $quote = $modelType::where('uuid', $quoteUID)->first();
            $quote->customerAdditionalInfo()->delete();

            for ($i = 0; $i < count($phones); $i++) {
                if (empty($phones[$i]) && empty($emails[$i])) {
                    continue;
                }
                $model = new CustomerAdditionalInfoModel();
                $model->mobile_no = $phones[$i];
                $model->email_address = $emails[$i];
                $model->customer_id = $customer ? $customer->id : null;
                $model->quote_type = $quoteType;
                $model->quote_request_id = $quote->id;
                $model->save();
            }
        }

        return 'success';
    }
}
