<?php

namespace App\Services\Bor;

use App\Mail\Bor\BorCompletionMail;
use App\Mail\Bor\BorInsurerNotificationMail;
use App\Mail\Bor\BorRequestMail;
use App\Models\BorLog;
use App\Models\PersonalQuote;
use App\Models\User;
use App\Services\Logger\LoggerService;

class BorEmailService
{
    /**
     * Send BOR request email to customer
     */
    public function sendBorRequestEmail(BorLog $borLog, ?string $portalUrl = null): bool
    {
        try {
            $customerData = $this->getCustomerData($borLog);
            $advisorData = $this->getAdvisorData($borLog);

            if (! $customerData || ! $customerData['email']) {
                LoggerService::info('BOR Request Email: Customer email not found', [
                    'bor_log_id' => $borLog->id,
                    'personal_quote_id' => $borLog->personal_quote_id,
                ]);

                return false;
            }

            if ($advisorData == null) {
                LoggerService::info('BOR Request Email: Advisor data not found', [
                    'bor_log_id' => $borLog->id,
                    'personal_quote_id' => $borLog->personal_quote_id,
                ]);

                return false;
            }

            $mail = new BorRequestMail($borLog, $customerData, $portalUrl, $advisorData);
            $success = $mail->sendViaBird();

            return $success;
        } catch (\Exception $e) {
            LoggerService::error('BOR Request Email Service failed', [
                'bor_log_id' => $borLog->id,
                'personal_quote_id' => $borLog->personal_quote_id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return false;
        }
    }

    /**
     * Send BOR completion email to customer
     */
    public function sendBorCompletionEmail(BorLog $borLog): bool
    {
        try {
            $customerData = $this->getCustomerData($borLog);
            $advisorData = $this->getAdvisorData($borLog);

            if (! $customerData || ! $customerData['email'] || $advisorData == null) {
                LoggerService::info('BOR Completion Email failed: Customer or Advisor email not found', [
                    'bor_log_id' => $borLog->id,
                    'personal_quote_id' => $borLog->personal_quote_id,
                ]);

                return false;
            }

            $mail = new BorCompletionMail($borLog, $customerData, $advisorData);

            return $mail->sendViaBird();

        } catch (\Exception $e) {
            LoggerService::error('BOR Completion Email Service failed', [
                'bor_log_id' => $borLog->id,
                'personal_quote_id' => $borLog->personal_quote_id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return false;
        }
    }

    /**
     * Send BOR notification email to insurer
     */
    public function sendBorInsurerNotification(BorLog $borLog): bool
    {
        try {
            $insurerContact = $borLog->insuranceContact;
            $advisorData = $this->getAdvisorData($borLog);

            if (! $insurerContact || $advisorData == null) {
                LoggerService::info('BOR Insurer Notification failed: Insurer contact or Advisor data not found', [
                    'bor_log_id' => $borLog->id,
                    'personal_quote_id' => $borLog->personal_quote_id,
                ]);

                return false;
            }

            $mail = new BorInsurerNotificationMail($borLog, $insurerContact, $advisorData);

            return $mail->sendViaBird();

        } catch (\Exception $e) {
            LoggerService::error('BOR Insurer Notification Service failed', [
                'bor_log_id' => $borLog->id,
                'personal_quote_id' => $borLog->personal_quote_id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return false;
        }
    }

    /**
     * Get customer data from the lead
     */
    private function getCustomerData(BorLog $borLog): ?array
    {
        try {
            $lead = PersonalQuote::find($borLog->personal_quote_id);

            if (! $lead) {
                LoggerService::error('BOR Email Service: Lead not found', [
                    'bor_log_id' => $borLog->id,
                    'personal_quote_id' => $borLog->personal_quote_id,
                ]);

                return null;
            }

            return [
                'email' => $lead->email,
                'first_name' => $lead->first_name,
                'last_name' => $lead->last_name,
                'company_name' => $lead->company_name ?? null,
                'mobile' => $lead->mobile_no,
            ];

        } catch (\Exception $e) {
            LoggerService::error('BOR Email Service: Failed to get customer data', [
                'bor_log_id' => $borLog->id,
                'personal_quote_id' => $borLog->personal_quote_id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function getAdvisorData(BorLog $borLog): ?array
    {
        $borLog->load('personalQuote.advisor');
        $advisor = User::find($borLog->personalQuote->advisor_id);
        if (! $advisor) {
            return null;
        }

        return [
            'advisorEmail' => $advisor->email,
            'advisorId' => $advisor->id,
            'advisorName' => $advisor->name,
            'advisorMobile' => $advisor->mobile_no,
            'advisorLandline' => $advisor->landline_no,
            'advisorWhatsApp' => $advisor->mobile_no,
            'mobileNoWithoutSpaces' => (! empty($advisor->mobile_no) ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : ''),
            'advisorProfilePath' => (! empty($advisor->profile_photo_path) ? $advisor->profile_photo_path : ''),
        ];
    }
}
