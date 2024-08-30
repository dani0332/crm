<?php

namespace App\Services;

use App\Models\PersonalQuote;

class PersonalQuoteService extends BaseService
{
    public function getEntityPlain($id)
    {
        return PersonalQuote::where('id', $id)->with([
            'payments' => function ($payment) {
                $payment->with([
                    'paymentSplits' => function ($paymentSplit) {
                        $paymentSplit->with([
                            'paymentStatus',
                            'paymentMethod',
                            'documents',
                            'verifiedByUser',
                            'processJob',
                        ]);
                        $paymentSplit->orderBy('sr_no');
                    },
                ]);
                $payment->orderBy('created_at');
            },
        ])->first();
    }
}
