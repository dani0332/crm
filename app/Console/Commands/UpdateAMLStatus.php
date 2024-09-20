<?php

namespace App\Console\Commands;

use App\Enums\AMLStatusCode;
use App\Enums\QuoteStatusId;
use App\Enums\QuoteTypeId;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateAMLStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'UpdateAMLStatus:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        info('Update Command Started');
        //Update Record using Quote Status Logs Table
        $leadTables = [
            ['table' => 'car_quote_request', 'quoteType' => QuoteTypeId::Car],
            ['table' => 'health_quote_request', 'quoteType' => QuoteTypeId::Health],
            ['table' => 'business_quote_request', 'quoteType' => QuoteTypeId::Business],
            ['table' => 'travel_quote_request', 'quoteType' => QuoteTypeId::Travel],
            ['table' => 'home_quote_request', 'quoteType' => QuoteTypeId::Home],
            ['table' => 'life_quote_request', 'quoteType' => QuoteTypeId::Life],
            ['table' => 'personal_quotes', 'quoteType' => QuoteTypeId::Pet],
            ['table' => 'personal_quotes', 'quoteType' => QuoteTypeId::Yacht],
            ['table' => 'personal_quotes', 'quoteType' => QuoteTypeId::Bike],
            ['table' => 'personal_quotes', 'quoteType' => QuoteTypeId::Cycle],
            ['table' => 'personal_quotes', 'quoteType' => QuoteTypeId::Jetski],
        ];

        foreach ($leadTables as $leadTable) {
            $table = $leadTable['table'];
            $quoteTypeId = $leadTable['quoteType'];

            $quoteStatusLogs = DB::table('quote_status_log as qsl')
                ->select('qsl.id', 'qsl.quote_request_id', 'qsl.current_quote_status_id', 'qsl.created_at')
                ->distinct()
                ->where('qsl.quote_type_id', $quoteTypeId)
                ->whereIn('qsl.current_quote_status_id', [
                    QuoteStatusId::AMLScreeningCleared,
                    QuoteStatusId::AMLScreeningFailed,
                ])
                ->whereNotNull('qsl.quote_request_id')
                ->whereIn('qsl.quote_request_id', function ($query) use ($table, $quoteTypeId) {
                    if ($table === 'personal_quotes') {
                        $query->select('id')
                            ->from($table)
                            ->where('quote_type_id', $quoteTypeId);
                    } else {
                        $query->select('id')->from($table);
                    }
                })
                ->whereIn('qsl.created_at', function ($query) {
                    $query->select(DB::raw('MAX(created_at)'))
                        ->from('quote_status_log')
                        ->groupBy('quote_request_id');
                })->orderBy('qsl.id');

            $quoteStatusLogs->chunk(2000, function ($logs) use ($table) {
                foreach ($logs as $log) {
                    $amlStatus = $log->current_quote_status_id === QuoteStatusId::AMLScreeningCleared ? AMLStatusCode::AMLScreeningCleared : AMLStatusCode::AMLScreeningFailed;
                    DB::table($table)
                        ->where("{$table}.id", '=', $log->quote_request_id)->update(['aml_status' => $amlStatus]);
                }
            });

        }
    }
}
