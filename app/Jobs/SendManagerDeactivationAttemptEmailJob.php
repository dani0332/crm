<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Logger\LoggerService;
use App\Services\SendEmailCustomerService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * SendManagerDeactivationAttemptEmailJob
 *
 * Sends an email notification to IT support when a manager deactivation is attempted
 * while they still have subordinates.
 */
class SendManagerDeactivationAttemptEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    private int $managerUserId;
    private array $subordinateIds;
    private int $attemptedByUserId;

    /**
     * Create a new job instance.
     *
     * @param  int  $managerUserId
     * @param  array  $subordinateIds
     * @param  int  $attemptedByUserId
     */
    public function __construct(int $managerUserId, array $subordinateIds, int $attemptedByUserId)
    {
        $this->managerUserId = $managerUserId;
        $this->subordinateIds = array_values(array_unique(array_map('intval', $subordinateIds)));
        $this->attemptedByUserId = $attemptedByUserId;
        $this->onQueue('shared');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $managerUser = User::query()
                ->with(['managers' => fn ($query) => $query->select('user_manager.id', 'email')])
                ->select(['id', 'name', 'email'])
                ->find($this->managerUserId);

            $attemptedBy = User::query()
                ->select(['id', 'name', 'email'])
                ->find($this->attemptedByUserId);

            if (! $managerUser || ! $attemptedBy) {
                LoggerService::error('Manager deactivation attempt email skipped: user not found', [
                    'manager_id' => $this->managerUserId,
                    'attempted_by' => $this->attemptedByUserId,
                ]);

                return;
            }

            $subordinates = User::query()
                ->select(['id', 'name', 'email'])
                ->activeUser()
                ->whereIn('id', $this->subordinateIds)
                ->get()
                ->map(fn (User $user) => (object) [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])
                ->all();

            $managerPayload = (object) $managerUser->only(['id', 'name', 'email','managers']);
            $attemptedByPayload = (object) $attemptedBy->only(['id', 'name', 'email']);

            LoggerService::info('Sending manager deactivation attempt email', [
                'manager_id' => $managerPayload->id,
                'subordinates_count' => count($subordinates),
                'attempted_by' => $attemptedByPayload->id,
            ]);

            app(SendEmailCustomerService::class)->sendManagerDeactivationAttemptEmail(
                $managerPayload,
                $subordinates,
                $attemptedByPayload
            );

            LoggerService::info('Manager deactivation attempt email sent successfully');

        } catch (Exception $e) {
            LoggerService::error('Error sending manager deactivation attempt email', [
                'manager_id' => $this->managerUserId,
                'error' => $e->getMessage(),
            ], $e);

            throw $e;
        }
    }
}
