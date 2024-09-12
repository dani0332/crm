<?php

namespace App\Console\Commands;

use App\Enums\AMLStatusCode;
use App\Enums\QuoteStatusId;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
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
        // Update AMl Status in Car Quote Request
        CarQuote::whereIn('quote_status_id', [
            QuoteStatusId::AMLScreeningCleared,
            QuoteStatusId::AMLScreeningFailed,
        ])
            ->update([
                'aml_status' => DB::raw('CASE
                                WHEN quote_status_id = '.QuoteStatusId::AMLScreeningCleared." THEN '".AMLStatusCode::AMLScreeningCleared."'
                                ELSE '".AMLStatusCode::AMLScreeningFailed."'
                                END"),
            ]);

        //Update Record using Quote Status Logs Table

        $quoteStatusLogs = DB::table('quote_status_log as qsl')
            ->select('qsl.id', 'qsl.quote_request_id', 'qsl.previous_quote_status_id')
            ->where('qsl.quote_type_id', QuoteTypeId::Car)
            ->whereIn('qsl.previous_quote_status_id', [
                QuoteStatusId::AMLScreeningCleared,
                QuoteStatusId::AMLScreeningFailed,
            ])
            ->whereNotNull('qsl.quote_request_id')
            ->whereIn('qsl.quote_request_id', function ($query) {
                $query->select('id')->from('car_quote_request');
            })
            ->join(
                DB::raw('(SELECT quote_request_id, MAX(created_at) as max_created_at
                  FROM quote_status_log
                  WHERE quote_type_id = '.QuoteTypeId::Car.'
                  AND previous_quote_status_id IN ('.QuoteStatusId::AMLScreeningCleared.', '.QuoteStatusId::AMLScreeningFailed.')
                  GROUP BY quote_request_id) as latest_log'),
                function ($join) {
                    $join->on('qsl.quote_request_id', '=', 'latest_log.quote_request_id')
                        ->on('qsl.created_at', '=', 'latest_log.max_created_at');
                }
            );

        DB::table('car_quote_request')
            ->joinSub($quoteStatusLogs, 'logs', function ($join) {
                $join->on('car_quote_request.id', '=', 'logs.quote_request_id');
            })
            ->update([
                'car_quote_request.aml_status' => DB::raw('
            CASE
                WHEN logs.previous_quote_status_id = '.QuoteStatusId::AMLScreeningCleared." THEN '".AMLStatusCode::AMLScreeningCleared."'
                ELSE '".AMLStatusCode::AMLScreeningFailed."'
            END
        "),
            ]);

    }
}
