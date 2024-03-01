<?php

namespace App\Repositories;

use App\Enums\SendUpdateLogStatusEnum;
use App\Models\SendUpdateLogDetails;

class SendUpdateLogDetailsRepository extends BaseRepository
{
    public function model()
    {
        return SendUpdateLogDetails::class;
    }

    public function fetchGetColumns(array $columns = ['*'])
    {
        return $this->get($columns);
    }

    public function fetchCreateOrUpdate($request)
    {
        try {
            $data = [
                'booking_date' => $request['booking_date'],
                'invoice_description' => $request['invoice_description'],
                'broker_invoice_number' => $request['broker_invoice_number'],
                'transaction_payment_status' => $request['transaction_payment_status'],
                'invoice_date' => $request['invoice_date'],
                'insurer_tax_invoice_number' => $request['insurer_tax_invoice_number'],
                'insurer_commission_invoice_number' => $request['insurer_commission_invoice_number'],
                'discount' => $request['discount'],
                'commission_percentage' => $request['commission_percentage'],
                'commission_vat_not_applicable' => $request['commission_vat_not_applicable'],
                'vat_on_commission' => $request['vat_on_commission'],
                'commission_vat_applicable' => strToFloat($request['commission_vat_applicable']),
                'total_commission' => $request['total_commission'],
                'total_vat_amount' => $request['total_vat_amount'],
                'price_vat_applicable' => strToFloat($request['price_vat_applicable']),
                'price_vat_not_applicable' => $request['price_vat_not_applicable'],
                'total_price' => $request['total_price'],
            ];
            // it will check if send update type is CPD then it will add reversal_invoice to $data because other send update types don't have 2 kind of
            // booking details, so we don't need to add null reversal_invoice on other options details.
            if ($request['send_update_type'] == SendUpdateLogStatusEnum::CPD) {
                $data = array_merge($data, ['reversal_invoice' => $request['reversal_invoice']]);
            }
            $res = $this->updateOrCreate(
                ['send_update_log_id' => $request['id']], [
                    'type' => $request['send_update_type'],
                    'data' => $data,
                ]
            );
        } catch (\Exception $ex) {
            $res = (object) [
                'message' => $ex->getMessage(),
            ];
            info($ex->getMessage());
        }

        return $res;
    }
}
