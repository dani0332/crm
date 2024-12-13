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
            // 'CAR',
            // 'TRAVEL',
            // 'HEALTH',
            'BIKE',
        ];

        foreach ($lobs as $lob) {
            info('InstantChatMigration - '.$lob.' Migration Initiated');
            AlfredChat::select('quote_id', 'quote_type', 'created_at')
                ->where('quote_type', $lob)
                ->chunk(500, function ($chunk) {
                    $items = $chunk->unique('quote_id');
                    foreach ($items as $item) {
                        if ($item['quote_id'] && ! is_null($item['created_at'])) {
                            $quoteModel = $this->getQuoteObjectBy(strtolower($item['quote_type']), $item['quote_id'], 'uuid');

                            if ($quoteModel) {
                                $carbonDate = Carbon::createFromFormat('d-M-Y h:ia', $item['created_at']);
                                $quote = $this->getQuoteDetailObject($item['quote_type'], $quoteModel->id);

                                if ($quote) {
                                    $existingDate = $quote->chat_initiated_at;

                                    // Update if no date exists or the new date is earlier
                                    if (is_null($existingDate) || $carbonDate->lessThan(Carbon::parse($existingDate))) {
                                        $quote->update(['chat_initiated_at' => $carbonDate->format('Y-m-d H:i:s')]);
                                        info('InstantChatMigration - Chat initiated at updated for Quote ID: '.$item['quote_id']);
                                    } else {
                                        info('InstantChatMigration - Existing chat_initiated_at is older or same, skipping update for Quote ID: '.$item['quote_id']);
                                    }
                                } else {
                                    info('InstantChatMigration - Quote Detail not found for Quote ID: '.$item['quote_id']);
                                }
                            } else {
                                info('InstantChatMigration - Quote Object not found for Quote ID: '.$item['quote_id']);
                            }
                        }
                    }
                });

            info('InstantChatMigration - '.$lob.' Migration Completed');
        }

        info('InstantChatMigration - All Migration Completed');
    }
}
