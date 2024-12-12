<?php

namespace App\Console\Commands;

use App\Models\AlfredChat;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Console\Command;

class InstantChatDataMigration extends Command
{
    use GenericQueriesAllLobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'InstantChatDataMigration:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to migrate data from mongodb to sql';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $lobs = [
            'CAR',
            'TRAVEL',
            'HEALTH',
            'BIKE',
        ];

        foreach ($lobs as $lob) {
            AlfredChat::select('quote_id', 'quote_type', 'created_at')
                ->where('quote_type', $lob)
                ->groupBy('quote_id')
                ->chunk(500, function ($chunk) {

                    foreach ($chunk as $item) {
                        if ($item['quote_id']) {
                            $quoteModel = $this->getQuoteObjectBy(strtolower($item['quote_type']), $item['quote_id'], 'uuid');

                            if ($quoteModel) {
                                $carbonDate = Carbon::createFromFormat('d-M-Y h:ia', $item['created_at']);

                                $quote = $this->getQuoteDetailObject($item['quote_type'], $quoteModel->id);
                                if ($quote) {
                                    $quote->update(['chat_initiated_at' => $carbonDate->format('Y-m-d H:i:s')]);
                                    info('Chat initiated at updated for Quote ID: '.$item['quote_id'].' Quote Type: '.$item['quote_type']);
                                }

                            } else {
                                info('Quote not found for Quote ID: '.$item['quote_id'].' Quote Type: '.$item['quote_type']);
                            }
                        }

                    }
                });
        }
    }
}
