<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendBookPolicyDocumentsJob;
use App\Jobs\WatermarkDocumentsJob;
use App\Models\DocumentType;
use App\Models\PersonalQuote;
use App\Models\QuoteType;
use App\Services\QuoteDocumentService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Console\Command;

class PolicyBulkSendDocuments extends Command
{
    use GenericQueriesAllLobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'policy:bulk-send-documents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Read a CSV file of emails, find PersonalQuote, and dispatch SendBookPolicyDocumentsJob for each.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $csvPath = 'storage/temp/sample.csv';

        if (! file_exists($csvPath)) {
            $this->error("File not found: $csvPath");

            return 1;
        }

        $handle = fopen($csvPath, 'r');
        if (! $handle) {
            $this->error("Unable to open file: $csvPath");

            return 1;
        }

        $header = fgetcsv($handle);
        if (! $header || ! in_array('email', $header)) {
            $this->error('CSV must have an email column.');
            fclose($handle);

            return 1;
        }
        $emailIndex = array_search('email', $header);
        $count = 0;
        $notFound = [];
        while (($row = fgetcsv($handle)) !== false) {
            $email = trim($row[$emailIndex] ?? '');

            if (! $email) {
                continue;
            }

            $personalQuote = PersonalQuote::where('email', $email)->latest()->first();
            $quoteType = QuoteType::select('code')->find($personalQuote->quote_type_id);
            $quoteObject = $this->getQuoteObjectBy(strtolower($quoteType->code), $personalQuote->quote_id, 'id');

            if (! $personalQuote || ! $quoteType || ! $quoteObject) {
                continue;
            }

            foreach ($quoteObject->documents as $document) {
                $documentType = DocumentType::where('code', $document->document_type_code)->first();
                $isWaterMarkQualifyDoc = app(QuoteDocumentService::class)->getWatermarkProperty($quoteObject, $documentType);

                if ($personalQuote && $isWaterMarkQualifyDoc && $documentType) {
                    WatermarkDocumentsJob::dispatchSync(
                        $document->id, $quoteObject->uuid, $documentType->id
                    );
                    // dd('watermark done, preparing for send policy documents');
                    $payload = (object) [
                        'model_type' => strtolower($quoteType->code),
                        'quote_id' => $quoteObject->id,
                    ];
                    SendBookPolicyDocumentsJob::dispatchSync($payload, $quoteObject->code, true);
                    $this->info("PolicyBulkSendDocuments - Dispatched for: $email (Quote ID: {$quoteObject->code})");
                    dd('email sent');
                    $count++;
                } else {
                    $notFound[] = $email;
                    $this->warn("PolicyBulkSendDocuments - No PersonalQuote found for: $email");
                }
            }
        }
        fclose($handle);
        $this->info("PolicyBulkSendDocuments - Total dispatched: $count");
        if ($notFound) {
            $this->warn('PolicyBulkSendDocuments - Emails not found: '.implode(', ', $notFound));
        }

        return 0;
    }
}
