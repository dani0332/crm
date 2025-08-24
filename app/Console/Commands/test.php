<?php

namespace App\Console\Commands;

use App\Enums\QuoteTypes;
use App\Jobs\OCR\PopulateDocumentData;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\DocumentType as ModelsDocumentType;
use App\Models\PersonalQuote;
use App\Models\SendUpdateLog;
use Dom\DocumentType;
use Illuminate\Console\Command;

class test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get the business quote with UUID S2EAHXWZ
        $quote = PersonalQuote::where('uuid', 'S2EAHXWZ')->first();
        
        if (!$quote) {
            $this->error('Personal Quote with UUID S2EAHXWZ not found!');
            return 1;
        }
        
        // Get the document type
        $documentType = ModelsDocumentType::where('code', 'TI')->first();
        
        if (!$documentType) {
            $this->error('Document Type with code TI not found!');
            $this->info('Available document types:');
            $this->table(['ID', 'Code', 'Text', 'Quote Type ID'], 
                ModelsDocumentType::select('id', 'code', 'text', 'quote_type_id')
                    ->orderBy('quote_type_id')
                    ->orderBy('code')
                    ->get()
                    ->toArray()
            );
            return 1;
        }
        
        $this->info('Found Personal Quote: ' . $quote->code);
        $this->info('Found Document Type: ' . $documentType->code . ' - ' . $documentType->text);
        
        // Debug information
        $this->info('Quote Type: ' . QuoteTypes::HOME->value);
        $this->info('Document Path: documents/home/68a84318eb9be_S2EAHXWZ_original_DEBIT_NOTE.pdf');
        
        // Dispatch the job
        PopulateDocumentData::dispatch(
            QuoteTypes::HOME,
            $quote,
            $documentType,
            'documents/home/68a84318eb9be_S2EAHXWZ_original_DEBIT_NOTE.pdf',
            'application/pdf',
            1063,
            false
        );
    }
}


// protected QuoteTypes $quoteType,
// protected Model $quote,
// protected DocumentType $documentType,
// protected string $documentPath,
// protected string $fileMimeType,
// protected int $userId,

// CarQuote::where('uuid', 'M6WWP6ME')->first(),
