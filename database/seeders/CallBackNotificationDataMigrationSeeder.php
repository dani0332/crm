<?php

namespace Database\Seeders;

use App\Enums\LeadSourceEnum;
use App\Models\Activities;
use App\Models\ActivityNotificationLogs;
use Illuminate\Database\Seeder;

class CallBackNotificationDataMigrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Activities::where('source', LeadSourceEnum::INSTANT_ALFRED)
            ->chunk(500, function ($activities) {
                foreach ($activities as $activity) {
                    $logsCount = ActivityNotificationLogs::where('activity_id', $activity->id)->count();
                    if ($logsCount > 0) {
                        $activity->update(['reminders_sent' => $logsCount]);
                    } else {
                        $activity->update(['reminders_sent' => 1]);
                    }
                }
            });
    }
}
