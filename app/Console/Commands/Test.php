<?php

namespace App\Console\Commands;

use App\Enums\QuoteTypes;
use App\Jobs\OCR\PopulateDocumentData;
use App\Models\CarQuote;
use App\Models\DocumentType;
use Illuminate\Console\Command;

class Test extends Command
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
        PopulateDocumentData::dispatchSync(QuoteTypes::CAR, CarQuote::find(195550), DocumentType::find(3094), 'documents/car/67d81cc73613e_EWZANBSU_original_tax-invoice.pdf');
    }
}
