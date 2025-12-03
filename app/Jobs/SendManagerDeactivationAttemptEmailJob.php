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
use Illuminate\Support\Collection;

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
    
    private $managerUser;
    private $subordinates;
    private $attemptedBy;

    /**
     * Create a new job instance.
     *
     * @param  User  $managerUser
     * @param  array|Collection  $subordinates
     * @param  User  $attemptedBy
     */
    public function __construct(User $managerUser, $subordinates, User $attemptedBy)
    {
        $this->managerUser = $managerUser->only(['id', 'name', 'email']);
        // Convert collection to array of objects with necessary properties to save space
        $this->subordinates = $subordinates instanceof Collection
            ? $subordinates->map(fn($u) => (object)['id' => $u->id, 'name' => $u->name, 'email' => $u->email])->toArray()
            : $subordinates;

        $this->attemptedBy = $attemptedBy->only(['id', 'name', 'email']);
        $this->onQueue('shared');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Convert arrays back to objects for compatibility with service method
            $managerUser = (object) $this->managerUser;
            $attemptedBy = (object) $this->attemptedBy;

            LoggerService::info('Sending manager deactivation attempt email', [
                'manager_id' => $managerUser->id,
                'subordinates_count' => count($this->subordinates),
                'attempted_by' => $attemptedBy->id,
            ]);

            app(SendEmailCustomerService::class)->sendManagerDeactivationAttemptEmail(
                $managerUser,
                $this->subordinates,
                $attemptedBy
            );

            LoggerService::info('Manager deactivation attempt email sent successfully');

        } catch (Exception $e) {
            LoggerService::error('Error sending manager deactivation attempt email', [
                'manager_id' => $this->managerUser['id'] ?? null,
                'error' => $e->getMessage(),
            ], $e);

            throw $e;
        }
    }
}
