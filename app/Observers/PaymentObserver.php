<?php

namespace App\Observers;

use App\Enums\PaymentFrequency;
use App\Enums\PaymentStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Events\AuthorisedPaymentCountUpdated;
use App\Models\Payment;
use App\Models\PersonalQuote;
use App\Models\User;
use App\Models\UserTeams;
use App\Repositories\PaymentRepository;
use App\Services\Logger\LoggerService;
use App\Services\SplitPaymentService;
use Illuminate\Support\Facades\DB;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        $this->updatePriceVat($payment);
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        // Only update VAT if total_price has changed
        if ($payment->isDirty('total_price')) {
            $this->updatePriceVat($payment);
        }
        // If payment status is changed to PAID, update the payment split to update the updated_at field of the payment split which is called the payment split observer
        if ($payment->frequency == PaymentFrequency::UPFRONT && $payment->isDirty('payment_status_id') && $payment->payment_status_id == PaymentStatusEnum::PAID) {
            LoggerService::info('Payment:Observer - Payment status changed to '.PaymentStatusEnum::PAID.' for payment code: '.$payment->code);
            $payment->paymentSplits()->first()->touch();
        }

        // If payment status changed to AUTHORISED, broadcast count updates to affected users
        if ($payment->isDirty('payment_status_id') && $payment->payment_status_id == PaymentStatusEnum::AUTHORISED) {
            $this->broadcastAuthorisedPaymentCountUpdate($payment);
        }
    }

    /**
     * Update the price VAT fields of the payment.
     */
    private function updatePriceVat(Payment $payment): void
    {
        $paymentCode = $payment->code;
        LoggerService::info('Payment:Observer - Starting VAT calculation for payment code: '.$paymentCode, extra: [
            'total_price' => $payment->total_price,
        ]);

        $modelType = null;
        $quoteId = null;

        if (! $payment->send_update_log_id) {
            $quote = $payment->paymentable;
            $quoteId = $quote->id;

            if ($payment->paymentable_type == PersonalQuote::class) {
                $modelType = QuoteTypes::getName($quote->quote_type_id)->value;
                LoggerService::info('Payment:Observer - Personal quote detected for payment code: '.$paymentCode);
            } else {
                $modelType = quoteTypeCode::getName($payment->paymentable_type);
                LoggerService::info('Payment:Observer - Other quote type detected for payment code: '.$paymentCode);
            }
        } else {
            LoggerService::info('Payment:Observer - Send update log payment detected for payment code: '.$paymentCode);
        }

        [$priceWithoutVat, $vat] = app(SplitPaymentService::class)->calculateMasterPriceAndVat(
            $payment->total_price,
            $modelType,
            $quoteId,
            $payment->code,
            $payment->send_update_log_id
        );

        LoggerService::info('Payment:Observer - VAT calculation completed for payment code: '.$paymentCode, extra: [
            'price_without_vat' => $priceWithoutVat,
            'vat' => $vat,
            'total_price' => $payment->total_price,
            'model_type' => $modelType,
            'quote_id' => $quoteId,
            'send_update_log_id' => $payment->send_update_log_id,
        ]);

        Payment::withoutEvents(function () use ($payment, $priceWithoutVat, $vat) {
            $payment->update([
                'price_vat_applicable' => $priceWithoutVat,
                'price_vat' => $vat,
            ]);

            LoggerService::info('Payment:Observer complete - update VAT and Price  for payment code: '.$payment->code);
        });
    }

    /**
     * Broadcast authorised payment count updates to affected users.
     */
    private function broadcastAuthorisedPaymentCountUpdate(Payment $payment): void
    {
        // Only process payments for PersonalQuote (getAuthorisePaymentCount only works with personal_quotes)
        if ($payment->paymentable_type !== PersonalQuote::class || ! $payment->paymentable) {
            return;
        }

        $quote = $payment->paymentable;
        $advisorId = $quote->advisor_id ?? null;

        if (! $advisorId) {
            return;
        }

        $advisor = User::find($advisorId);
        if (! $advisor) {
            return;
        }

        // Get all users who should see updated counts: advisor + managers of advisor's teams
        $affectedUserIds = [$advisorId];

        // Get advisor's team IDs
        $advisorTeamIds = $advisor->getUserTeamIds();

        if (! empty($advisorTeamIds)) {
            // Get all managers who manage teams that the advisor belongs to
            $managerRoles = [
                RolesEnum::CarManager,
                RolesEnum::HealthManager,
                RolesEnum::TravelManager,
                RolesEnum::LifeManager,
                RolesEnum::HomeManager,
                RolesEnum::PetManager,
                RolesEnum::BikeManager,
                RolesEnum::CycleManager,
                RolesEnum::YachtManager,
                RolesEnum::JetskiManager,
                RolesEnum::BusinessManager,
            ];

            $managerUserIds = User::whereHas('roles', function ($query) use ($managerRoles) {
                $query->whereIn('name', $managerRoles);
            })
                ->whereHas('teams', function ($query) use ($advisorTeamIds) {
                    $query->whereIn('id', $advisorTeamIds);
                })
                ->pluck('id')
                ->toArray();

            $affectedUserIds = array_unique(array_merge($affectedUserIds, $managerUserIds));
        }

        // Calculate and broadcast count for each affected user
        $paymentRepository = app(PaymentRepository::class);

        foreach ($affectedUserIds as $userId) {
            $user = User::find($userId);
            if (! $user) {
                continue;
            }

            $count = $paymentRepository->getAuthorisePaymentCount($user);
            event(new AuthorisedPaymentCountUpdated($userId, $count));
        }

        LoggerService::info('Payment:Observer - Broadcasted authorised payment count updates for payment code: '.$payment->code, [
            'affected_users' => $affectedUserIds,
        ]);
    }
}
