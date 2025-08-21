<?php

namespace App\Services\Bor;

use App\Mail\Bor\BorRequestMail;
use App\Mail\Bor\BorCompletionMail;
use App\Mail\Bor\BorInsurerNotificationMail;
use App\Mail\Bor\BorStatusUpdateMail;
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
            
            if (!$customerData || !$customerData['email']) {
                LoggerService::error('BOR Request Email: Customer email not found', [
                    'bor_log_id' => $borLog->id,
                    'lead_id' => $borLog->lead_id
                ]);
                return false;
            }

            if($advisorData == null) {
                LoggerService::error('BOR Request Email: Advisor data not found', [
                    'bor_log_id' => $borLog->id,
                    'lead_id' => $borLog->lead_id
                ]);
                return false;
            }

            $mail = new BorRequestMail($borLog, $customerData, $portalUrl, $advisorData);
            $success = $mail->sendViaBird();

            return $success;
        } catch (\Exception $e) {
            LoggerService::error('BOR Request Email Service failed', [
                'bor_log_id' => $borLog->id,
                'lead_id' => $borLog->lead_id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
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
            
            if (!$customerData || !$customerData['email']) {
                LoggerService::error('BOR Completion Email: Customer email not found', [
                    'bor_log_id' => $borLog->id,
                    'lead_id' => $borLog->lead_id
                ]);
                return false;
            }

            $mail = new BorCompletionMail($borLog, $customerData, $advisorData);
            return $mail->sendViaBird();

        } catch (\Exception $e) {
            LoggerService::error('BOR Completion Email Service failed', [
                'bor_log_id' => $borLog->id,
                'lead_id' => $borLog->lead_id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
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


            if(!$insurerContact) {
                LoggerService::info('BOR Insurer Notification: Insurer contact not found', [
                    'bor_log_id' => $borLog->id,
                    'lead_id' => $borLog->lead_id,
                ]);

                return false;
            }

            $mail = new BorInsurerNotificationMail($borLog, $insurerContact, $advisorData);
            return $mail->sendViaBird();

        } catch (\Exception $e) {
            LoggerService::error('BOR Insurer Notification Service failed', [
                'bor_log_id' => $borLog->id,
                'lead_id' => $borLog->lead_id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }
    }

    /**
     * Send BOR status update email to customer
     */
    public function sendBorStatusUpdateEmail(BorLog $borLog, string $oldStatus, string $newStatus): bool
    {
        try {
            // Skip email for certain status changes that don't require customer notification
            if ($this->shouldSkipStatusUpdateEmail($oldStatus, $newStatus)) {
                return true;
            }

            $customerData = $this->getCustomerData($borLog);
            
            if (!$customerData || !$customerData['email']) {
                LoggerService::error('BOR Status Update Email: Customer email not found', [
                    'bor_log_id' => $borLog->id,
                    'lead_id' => $borLog->lead_id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus
                ]);
                return false;
            }

            // $mail = new BorStatusUpdateMail($borLog, $customerData, $oldStatus, $newStatus);
            // return $mail->sendViaBird();
            return true;

        } catch (\Exception $e) {
            LoggerService::error('BOR Status Update Email Service failed', [
                'bor_log_id' => $borLog->id,
                'lead_id' => $borLog->lead_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
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
            $lead = PersonalQuote::find($borLog->lead_id);
            
            if (!$lead) {
                LoggerService::error('BOR Email Service: Lead not found', [
                    'bor_log_id' => $borLog->id,
                    'lead_id' => $borLog->lead_id
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
                'lead_id' => $borLog->lead_id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function getAdvisorData(BorLog $borLog): ?array
    {
        $borLog->load('personalQuote.advisor');
        $advisor = User::find($borLog->personalQuote->advisor_id);
        if(!$advisor) {
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

    /**
     * Determine if status update email should be skipped
     */
    private function shouldSkipStatusUpdateEmail(string $oldStatus, string $newStatus): bool
    {
        // Skip emails for internal status changes that don't affect customer
        $skipCombinations = [
            ['pending', 'in_progress'], // Internal processing start
        ];

        foreach ($skipCombinations as $combination) {
            if ($combination[0] === $oldStatus && $combination[1] === $newStatus) {
                return true;
            }
        }

        return false;
    }

    /**
     * Send all relevant emails when BOR is completed
     */
    public function sendBorCompletionNotifications(BorLog $borLog, string $insurerEmail = null): array
    {
        $results = [];

        // Send completion email to customer
        $results['customer_completion'] = $this->sendBorCompletionEmail($borLog);

        LoggerService::info('BOR Completion Notifications sent', [
            'bor_log_id' => $borLog->id,
            'lead_id' => $borLog->lead_id,
            'results' => $results
        ]);

        return $results;
    }
} 