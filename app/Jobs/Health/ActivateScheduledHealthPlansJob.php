<?php

namespace App\Jobs\Health;

use App\Enums\HealthPlanRateSheetStatusEnum;
use App\Models\HealthPlan;
use App\Models\HealthRate;
use App\Models\HealthRateControl;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ActivateScheduledHealthPlansJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        LoggerService::info(self::class.' - Started processing scheduled health plans');

        $today = Carbon::today();

        $this->archiveExpiredControls($today);
        $this->activateScheduledControls($today);

        LoggerService::info(self::class.' - Finished processing scheduled health plans');
    }

    private function archiveExpiredControls(Carbon $today): void
    {
        $expiredControls = HealthRateControl::with('healthPlan')
            ->where('status', HealthPlanRateSheetStatusEnum::ACTIVE->value)
            ->whereDate('effective_to', '<', $today)
            ->get();

        if ($expiredControls->isEmpty()) {
            return;
        }

        $controlIds = $expiredControls->pluck('id');
        $planIds = $expiredControls->pluck('healthPlan')->pluck('id')->unique();

        DB::transaction(function () use ($controlIds, $planIds) {
            HealthRate::whereIn('health_rate_control_id', $controlIds)
                ->update(['status' => HealthPlanRateSheetStatusEnum::ARCHIVED->value]);

            HealthRateControl::whereIn('id', $controlIds)
                ->update(['status' => HealthPlanRateSheetStatusEnum::ARCHIVED->value]);

            HealthPlan::whereIn('id', $planIds)
                ->update(['status' => HealthPlanRateSheetStatusEnum::ARCHIVED->value]);
        });

        LoggerService::info(self::class." - Archived expired rate control IDs: [{$controlIds->implode(', ')}], plan IDs: [{$planIds->implode(', ')}]");
    }

    private function activateScheduledControls(Carbon $today): void
    {
        $scheduledControls = HealthRateControl::with('healthPlan')
            ->where('status', HealthPlanRateSheetStatusEnum::SCHEDULED->value)
            ->whereDate('effective_from', $today)
            ->get();

        foreach ($scheduledControls as $rateControl) {
            $plan = $rateControl->healthPlan;

            if (! $plan) {
                continue;
            }

            DB::transaction(function () use ($plan, $rateControl, $today) {
                $this->archiveCurrentlyActivePlan($plan, $today);

                $prevPlanVersion = $plan->version;
                $prevControlVersion = $rateControl->version;
                $newVersion = $this->deriveMajorVersion($rateControl->version);

                $rateControl->rates()->update([
                    'status' => HealthPlanRateSheetStatusEnum::ACTIVE->value,
                    'version' => $newVersion,
                ]);

                $rateControl->status = HealthPlanRateSheetStatusEnum::ACTIVE->value;
                $rateControl->version = $newVersion;
                $rateControl->save();

                if ($plan->status === HealthPlanRateSheetStatusEnum::SCHEDULED->value) {
                    $plan->version = $newVersion;
                    $plan->parent_id = null;
                }

                $plan->status = HealthPlanRateSheetStatusEnum::ACTIVE->value;
                $plan->save();

                LoggerService::info(self::class." - Activated health plan ID: {$plan->id}, rate control ID: {$rateControl->id}", [
                    'plan_version' => ['previous' => $prevPlanVersion, 'new' => $plan->version],
                    'rate_control_version' => ['previous' => $prevControlVersion, 'new' => $newVersion],
                ]);
            });
        }
    }

    private function archiveCurrentlyActivePlan(HealthPlan $incomingPlan, Carbon $today): void
    {
        $activePlan = HealthPlan::where('code', $incomingPlan->code)
            ->where('status', HealthPlanRateSheetStatusEnum::ACTIVE->value)
            ->first();

        if (! $activePlan) {
            return;
        }

        $activeControl = HealthRateControl::where('health_plan_id', $activePlan->id)
            ->where('status', HealthPlanRateSheetStatusEnum::ACTIVE->value)
            ->first();

        if ($activeControl) {
            $activeControl->rates()->update(['status' => HealthPlanRateSheetStatusEnum::ARCHIVED->value]);

            $activeControl->effective_to = $today->copy()->subDay();
            $activeControl->status = HealthPlanRateSheetStatusEnum::ARCHIVED->value;
            $activeControl->save();
        }

        if ($activePlan->id !== $incomingPlan->id) {
            $activePlan->status = HealthPlanRateSheetStatusEnum::ARCHIVED->value;
            $activePlan->parent_id = $incomingPlan->id;
            $activePlan->save();

            LoggerService::info(self::class." - Archived previously active plan ID: {$activePlan->id}");
        }
    }

    private function deriveMajorVersion(float $version): float
    {
        return (float) (ceil($version).'.0');
    }
}
