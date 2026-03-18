<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\QuoteTypes;
use App\Models\EmailStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class WhatsAppMessageStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 15;
    public int $backoff = 300;

    /** @var object{message_id?: string, status?: string, mobile?: string, type?: string, reason?: string|null, message_tags?: array<int, string>} */
    private object $messageData;

    /** @param  object{message_id: string, status: string, mobile: string, type: string, reason?: string|null, message_tags?: array<int, string>}  $messageData */
    public function __construct(object $messageData)
    {
        $this->messageData = $messageData;
    }

    public function handle(): void
    {
        try {
            if (DB::getDefaultConnection() !== 'mysql') {
                DB::setDefaultConnection('mysql');
            }

            $messageId = $this->messageData->message_id ?? null;
            $status = $this->messageData->status ?? null;
            $mobile = $this->messageData->mobile ?? null;

            if (empty($messageId) || empty($status) || empty($mobile)) {
                info('WhatsAppMessageStatusJob - Skipping: missing required fields', [
                    'message_id' => $messageId,
                    'status' => $status,
                    'mobile' => $mobile,
                ]);

                return;
            }

            $quoteContext = $this->resolveQuoteFromMessageTags();

            $existingRecord = EmailStatus::where('msg_id', $messageId)
                ->where('type', 'whatsApp')
                ->where('mobile', $mobile)
                ->where('email_status', $status)
                ->first();

            if ($existingRecord) {
                info('WhatsAppMessageStatusJob - Record already exists', [
                    'msg_id' => $messageId,
                ]);

                return;
            }

            $baseRecord = EmailStatus::where('msg_id', $messageId)
                ->where('type', 'whatsApp')
                ->where('mobile', $mobile)
                ->first();

            if ($baseRecord) {
                $newRecord = new EmailStatus;
                $newRecord->type = 'whatsApp';
                $newRecord->mobile = $mobile;
                $newRecord->msg_id = $messageId;
                $newRecord->email_status = $status;
                $newRecord->reason = $this->messageData->reason ?? $baseRecord->reason;
                $newRecord->quote_type_id = $baseRecord->quote_type_id;
                $newRecord->quote_id = $baseRecord->quote_id;
                $newRecord->save();

                info('WhatsAppMessageStatusJob - Status update created', [
                    'msg_id' => $messageId,
                    'status' => $status,
                ]);

                return;
            }

            $newRecord = new EmailStatus;
            $newRecord->type = 'whatsApp';
            $newRecord->mobile = $mobile;
            $newRecord->msg_id = $messageId;
            $newRecord->email_status = $status;
            $newRecord->reason = $this->messageData->reason ?? null;
            if ($quoteContext) {
                $newRecord->quote_type_id = $quoteContext['quote_type_id'];
                $newRecord->quote_id = $quoteContext['quote_id'];
            }
            $newRecord->save();

            info('WhatsAppMessageStatusJob - New record created', [
                'msg_id' => $messageId,
                'status' => $status,
                'quote_context' => $quoteContext ? 'resolved' : 'none',
            ]);
        } catch (\Throwable $th) {
            info('WhatsAppMessageStatusJob - Error', [
                'message' => $th->getMessage(),
                'line' => $th->getLine(),
                'file' => $th->getFile(),
                'trace' => $th->getTraceAsString(),
            ]);
            throw $th;
        }
    }

    /**
     * Parse messageTags for ref-id format (e.g. HEA-12HENE, CAR-abc123-uuid).
     * First part = quote type code (HEA, CAR, etc.), second part = uuid or ref suffix.
     *
     * @return array{quote_type_id: int, quote_id: int}|null
     */
    private function resolveQuoteFromMessageTags(): ?array
    {
        $messageTags = $this->messageData->message_tags ?? [];
        if (empty($messageTags) || ! is_array($messageTags)) {
            return null;
        }

        foreach ($messageTags as $tag) {
            if (! is_string($tag) || strpos($tag, '-') === false) {
                continue;
            }

            $parts = explode('-', $tag, 2);
            $typeCode = strtoupper($parts[0] ?? '');
            $uuidOrRef = $parts[1] ?? '';

            if (empty($typeCode) || empty($uuidOrRef)) {
                continue;
            }

            $quoteType = QuoteTypes::getNameShortCode($typeCode);
            if (! $quoteType) {
                continue;
            }

            $quoteTypeId = QuoteTypes::getId($quoteType);
            if (! $quoteTypeId) {
                continue;
            }

            $model = $quoteType->model();
            $quote = $model->where('code', $tag)->orWhere('uuid', $uuidOrRef)->first();

            if ($quote) {
                return [
                    'quote_type_id' => $quoteTypeId,
                    'quote_id' => $quote->id,
                ];
            }
        }

        return null;
    }
}
