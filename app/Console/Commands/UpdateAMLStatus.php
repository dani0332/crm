<?php

namespace App\Console\Commands;

use App\Enums\AMLStatusCode;
use App\Enums\QuoteStatusId;
use App\Enums\QuoteTypeId;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Models\TravelQuote;
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
        // Update AMl Status in Quote Request Tables
        $quoteTypes = [
            CarQuote::class,
            HealthQuote::class,
            TravelQuote::class,
            BusinessQuote::class,
            HomeQuote::class,
            LifeQuote::class,
            PersonalQuote::class,
        ];

        foreach ($quoteTypes as $quoteType) {
            $quoteType::whereIn('quote_status_id', [
                QuoteStatusId::AMLScreeningCleared,
                QuoteStatusId::AMLScreeningFailed,
            ])->update([
                'aml_status' => DB::raw('CASE
            WHEN quote_status_id = '.QuoteStatusId::AMLScreeningCleared." THEN '".AMLStatusCode::AMLScreeningCleared."'
            ELSE '".AMLStatusCode::AMLScreeningFailed."'
            END"),
            ]);
        }
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
                ->select('qsl.id', 'qsl.quote_request_id', 'qsl.previous_quote_status_id')
                ->where('qsl.quote_type_id', $quoteTypeId)
                ->whereIn('qsl.previous_quote_status_id', [
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
                ->join(
                    DB::raw('(SELECT quote_request_id, MAX(created_at) as max_created_at
                      FROM quote_status_log
                      WHERE quote_type_id = '.$quoteTypeId.'
                      AND previous_quote_status_id IN ('.QuoteStatusId::AMLScreeningCleared.', '.QuoteStatusId::AMLScreeningFailed.')
                      GROUP BY quote_request_id) as latest_log'),
                    function ($join) {
                        $join->on('qsl.quote_request_id', '=', 'latest_log.quote_request_id')
                            ->on('qsl.created_at', '=', 'latest_log.max_created_at');
                    }
                );


            // Update aml_status column in Tables
            DB::table($table)
                ->joinSub($quoteStatusLogs, 'logs', function ($join) use ($table) {
                    $join->on("$table.id", '=', 'logs.quote_request_id');
                })
                ->update([
                    "$table.aml_status" => DB::raw('
                CASE
                    WHEN logs.previous_quote_status_id = '.QuoteStatusId::AMLScreeningCleared." THEN '".AMLStatusCode::AMLScreeningCleared."'
                    ELSE '".AMLStatusCode::AMLScreeningFailed."'
                END
            "),
                ]);
        }


    }
}
