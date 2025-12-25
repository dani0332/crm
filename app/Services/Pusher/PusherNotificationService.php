<?php 

namespace App\Services\Pusher;

use App\Services\BaseService;
use App\Models\User;
use App\Events\AdvisorNotificationPushed;
use App\Services\Logger\LoggerService;

class PusherNotificationService extends BaseService
{
    public function sendSTPAdvisorNotification($lead)
    {
        try {
        $advisor = User::where('id', $lead->advisor_id)->first();
        
        if (!$advisor || !$advisor->id) {
            LoggerService::error(self::class." - Advisor not found for lead UUID: {$lead->uuid}");
            return;
        }
        
        $pusherData = [
            'title' => 'New STP Advisor Notification',
            'message' => "A new STP advisor notification workflow has been triggered for ",
            'lead_uuid' => $lead->uuid,
            'timestamp' => now()->toDateTimeString()
        ];
        
        LoggerService::info(self::class." - Broadcasting STP notification to advisor (ID: {$advisor->id}) for UUID: {$lead->uuid}");
        broadcast(new AdvisorNotificationPushed($pusherData, $advisor->id));
        LoggerService::info(self::class." - Pusher notification broadcasted to advisor (ID: {$advisor->id}) for UUID: {$lead->uuid}");
        } catch (\Exception $e) {
            LoggerService::error(self::class.': STP advisor notification broadcasting failed',  [
                'uuid' => $lead->uuid ?? null,
                'advisor_id' => $advisor->id ?? $lead->advisor_id ?? null,
                'error' => $e->getMessage(),
            ]);
            return;
        }
    }
}