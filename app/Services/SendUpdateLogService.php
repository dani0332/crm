<?php

namespace App\Services;

use App\Enums\SendUpdateLogStatusEnum;
use App\Models\Payment;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LookupRepository;

class SendUpdateLogService
{
    public function isNegativeValue($sendUpdateLog): bool
    {
        $category = LookupRepository::where('id', $sendUpdateLog->category_id)->value('code');

        if (in_array($category, [SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR])) {
            return true;
        }

        if ($category == SendUpdateLogStatusEnum::EF) {
            $option = LookupRepository::where('id', $sendUpdateLog->option_id)->value('code');
            if (in_array($option, [
                SendUpdateLogStatusEnum::MPC,
                SendUpdateLogStatusEnum::MDOM,
                SendUpdateLogStatusEnum::MDOV,
                SendUpdateLogStatusEnum::ED,
                SendUpdateLogStatusEnum::DM,
            ])) {
                return true;
            }
        }

        return false;
    }

    public function getInvoiceDescription($quote, $quoteType, $insurance_provider_id)
    {
        $insuranceProviderCode = InsuranceProviderRepository::where('id', $insurance_provider_id)->value('code');
        $insuranceProviderLeadCount = Payment::where('insurance_provider_id', '=', $insurance_provider_id)->count();

        return [
            'broker_invoice_number' => $insuranceProviderCode.$insuranceProviderLeadCount,
            'invoice_description' => $insuranceProviderCode.'-'.$quoteType.'-'.$quote->policy_number,
        ];
    }
}
