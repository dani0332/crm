<?php

namespace App\Console\Commands;

use App\Enums\AMLStatusCode;
use App\Enums\QuoteStatusId;
use App\Enums\QuoteTypes;
use App\Models\QuoteStatusLog;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateAMLStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-aml-status:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get latest AML Status from Quote Status Logs and update into Quote Request Table';
    use GenericQueriesAllLobs;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        info('Cmd:UpdateAMLStatus - AML status update command started');
        $getQuoteStatuses = QuoteStatusLog::whereIn('current_quote_status_id', [QuoteStatusId::AMLScreeningCleared, QuoteStatusId::AMLScreeningFailed])
            ->whereNotNull('quote_type_id')
            ->whereNotNull('quote_request_id')
            ->where('created_at', '>=', Carbon::create(2024, 01, 01))
            ->chunkById(2000, function ($quoteStatusLogs) {
                foreach ($quoteStatusLogs as $quoteStatusLog) {
                    $amlStatus = $quoteStatusLog->current_quote_status_id === QuoteStatusId::AMLScreeningCleared ? AMLStatusCode::AMLScreeningCleared : AMLStatusCode::AMLScreeningFailed;
                    $quoteType = QuoteTypes::getName($quoteStatusLog->quote_type_id)->value ?? null;
                    $getQuoteObject = $this->getModelObject($quoteType);
                    if (class_exists($getQuoteObject)) {
                        $getQuoteDetails = $getQuoteObject::where('id', $quoteStatusLog->quote_request_id)->first();

                        if ($getQuoteDetails) {
                            info('cmd:UpdateAMLStatus - QuoteType:'.$quoteType.' - QuoteID:'.$quoteStatusLog->quote_request_id.' - AMLStatus:'.$amlStatus);
                            $getQuoteDetails->update(['aml_status' => $amlStatus]);
                        }
                    }

                }
            });
        info('Cmd:UpdateAMLStatus - AML status update command completed');
    }
}
