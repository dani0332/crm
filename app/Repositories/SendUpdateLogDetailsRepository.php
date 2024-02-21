<?php

namespace App\Repositories;

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

    public function fetchCreateOrUpdate($data)
    {
        try {
            $res = $this->updateOrCreate(
                ['send_update_log_id' => $data->id], [
                    'type' => $data->send_update_type,
                    'data' => [
                        'booking_date' => $data->booking_date,
                        'invoice_description' => $data->invoice_description,
                        'broker_invoice_number' => $data->broker_invoice_number,
                        'transaction_payment_status' => $data->transaction_payment_status,
                        'invoice_date' => $data->invoice_date,
                        'insurer_tax_invoice_number' => $data->insurer_tax_invoice_number,
                        'insurer_commission_invoice_number' => $data->insurer_commission_invoice_number,
                        'discount' => $data->discount,
                        'commission_percentage' => $data->commission_percentage,
                        'commission_vat_not_applicable' => $data->commission_vat_not_applicable,
                        'vat_on_commission' => $data->vat_on_commission,
                        'commission_vat_applicable' => strToFloat($data->commission_vat_applicable),
                        'total_commission' => $data->total_commission,
                        'total_vat_amount' => $data->total_vat_amount,
                        'price_vat_applicable' => strToFloat($data->price_vat_applicable),
                        'price_vat_not_applicable' => $data->price_vat_not_applicable,
                        'total_price' => $data->total_price,
                    ],
                ]
            );
        } catch (\Exception $th) {
            $res = (object) [
                'message' => $th->getMessage(),
            ];
        }

        return $res;
    }
}
