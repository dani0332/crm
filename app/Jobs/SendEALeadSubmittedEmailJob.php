<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EaModelEnum;
use App\Enums\EnvEnum;
use App\Enums\RolesEnum;
use App\Models\User;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendEALeadSubmittedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        private readonly Model $quote,
        private readonly string $quoteType,
        private readonly ?int $templateId = null,
    ) {}

    public function handle(): void
    {
        $templateId = $this->templateId ?? (int) getAppStorageValueByKey(ApplicationStorageEnums::EA_LEAD_SUBMITTED_TEMPLATE_ID, false, true);

        if (! $templateId) {
            LoggerService::warning('SendEALeadSubmittedEmailJob: Brevo template not configured');

            return;
        }

        $this->quote->loadMissing(['advisor', 'leadGenerator', 'expertAdvisor']);

        if (! $this->quote->advisor?->email) {
            LoggerService::info('SendEALeadSubmittedEmailJob: No advisor email, skipping', ['ref_id' => $this->quote->code ?? '']);

            return;
        }

        $managerEmails = User::whereHas('roles', fn ($q) => $q->where('name', RolesEnum::EAManager))
            ->pluck('email')
            ->filter()
            ->values()
            ->all();

        $ccEmails = collect()
            ->when($this->quote->leadGenerator?->email, fn ($c) => $c->push(['email' => $this->quote->leadGenerator->email]))
            ->merge(array_map(fn ($email) => ['email' => $email], $managerEmails))
            ->values()
            ->all();

        $appEnv = config('constants.APP_ENV');
        $tag = $appEnv === EnvEnum::PRODUCTION ? 'ea-lead-submitted' : "{$appEnv}-ea-lead-submitted";

        $body = [
            'to' => [['email' => $this->quote->advisor->email, 'name' => $this->quote->advisor->name]],
            'cc' => $ccEmails ?: null,
            'templateId' => $templateId,
            'params' => [
                'refID' => $this->quote->code,
                'advisorName' => $this->quote->advisor->name,
                'leadGenerator' => $this->quote->leadGenerator?->name ?? '',
                'lob' => $this->quoteType,
                'eaModel' => $this->quote->ea_model instanceof EaModelEnum ? $this->quote->ea_model->value : $this->quote->ea_model,
                'leadSource' => $this->quote->source,
                ...($this->quote->ea_model === EaModelEnum::Collaborate ? ['eaAdvisor' => $this->quote->expertAdvisor?->name ?? ''] : []),
            ],
            'tags' => [$tag],
        ];

        LoggerService::info('SendEALeadSubmittedEmailJob: Sending to Brevo', [
            'ref_id' => $this->quote->code,
            'uuid' => $this->quote->uuid,
            'template_id' => $templateId,
            'to' => $this->quote->advisor->email,
            'cc' => array_column($ccEmails, 'email'),
            'params' => $body['params'],
            'tag' => $tag,
        ]);

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'api-key' => config('constants.SENDINBLUE_KEY'),
            'Content-Type' => 'application/json',
        ])->post(config('constants.SIB_URL'), $body);

        LoggerService::info('SendEALeadSubmittedEmailJob: Brevo response', [
            'ref_id' => $this->quote->code,
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        if ($response->failed()) {
            LoggerService::warning('SendEALeadSubmittedEmailJob: Brevo returned non-2xx — will retry', [
                'ref_id' => $this->quote->code,
                'uuid' => $this->quote->uuid,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            $response->throw();
        }

        LoggerService::info('SendEALeadSubmittedEmailJob: Sent via Brevo', ['ref_id' => $this->quote->code]);
    }

    public function failed(\Throwable $exception): void
    {
        LoggerService::error('SendEALeadSubmittedEmailJob: Failed', [
            'ref_id' => $this->quote->code ?? '',
            'error' => $exception->getMessage(),
        ]);
    }
}
