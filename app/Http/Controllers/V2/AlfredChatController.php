<?php

namespace App\Http\Controllers\V2;

use App\Enums\InstantChatReportsEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Exports\InstantChatConsolidatedExport;
use App\Exports\InstantChatDetailedExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\AlfredChatRequest;
use App\Models\AlfredChat;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\QuoteBatches;
use App\Models\QuoteStatus;
use App\Models\TravelQuote;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AlfredChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:'.PermissionsEnum::INSTANT_ALFRED_CHAT_LOGS, ['only' => ['logs']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(AlfredChatRequest $request)
    {
        $chat = AlfredChat::raw(function ($collection) use ($request) {
            return $collection->aggregate([
                [
                    '$match' => [ // $match is a group operator to filter the records just like where clause in SQL
                        'quote_id' => $request->quoteId,
                        'quote_type' => $request->quoteType,
                    ],
                ],
                [
                    '$group' => [
                        '_id' => [ // _id is a group operator to group the records
                            '$dateToString' => [ // $dateToString is an aggregation operator to convert date to string
                                'timezone' => '+04:00',
                                'format' => '%Y-%m-%d', // format of the date
                                'date' => ['$toDate' => '$created_at'], // $toDate is an aggregation operator to convert string to date
                            ],
                        ],
                        'role' => ['$first' => '$role'], //$first is used to add role field of the first occurrence of the group
                        'msg' => ['$first' => '$msg'], //$first is used to add msg field  of the first occurrence of the group
                        'count' => ['$sum' => 1], // $sum is used to count the number of records in the group
                    ],
                ],
                [
                    '$sort' => ['_id' => -1], // Sort by _id (date) in descending order
                ],
            ]);
        });

        if ($chat->isEmpty()) {
            return response()->json(['message' => 'No chat available']);
        }

        return response()->json(['data' => $chat]);
    }

    public function getChatByDate(AlfredChatRequest $request)
    {
        // $dateFrom = Carbon::createFromFormat('Y-m-d', $request->created_at)->startOfDay()->toIso8601String();
        // $dateTo = Carbon::createFromFormat('Y-m-d', $request->created_at)->endOfDay()->toIso8601String();

        $chat = AlfredChat::where('quote_id', $request->quoteId)
            ->where('quote_type', $request->quoteType)
            ->get();

        if ($chat->isEmpty()) {
            return response()->json(['message' => 'No chat available']);
        }

        return response()->json(['data' => $chat]);
    }

    public function logs(Request $request, $exportChat = false)
    {

        $modelType = $request->quoteType ?? 'Car';
        $nameSpace = 'App\\Models\\';
        $modelType = (in_array(ucwords($modelType), newUi()) && checkPersonalQuotes(ucwords($modelType))) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($modelType).'Quote';

        $data = [];
        if ($modelType == CarQuote::class) {
            $data = CarQuote::with(['batch' => function ($query){
                $query->select('id', 'name');
            },'carQuoteRequestDetail' => function ($query) {
                $query->select('id', 'car_quote_request_id', 'chat_initiated_at'); // specify keys from carQuoteRequestDetail
            }, 'paymentStatus'])
            ->select('id', 'uuid', 'code', 'quote_batch_id', 'payment_status_id') // specify keys from CarQuote
            ->whereHas('carQuoteRequestDetail', function ($query) {
                $query->whereNotNull('chat_initiated_at');
            })
            ->where(function ($query) use ($request, $modelType) {
                $this->processChatFilters($request, $query,  $modelType);
            });
        }elseif($modelType == HealthQuote::class) {
            $data = HealthQuote::with(['healthQuoteRequestDetail' => function ($query) {
                $query->select('id', 'health_quote_request_id', 'chat_initiated_at'); // specify keys from healthQuoteRequestDetail
            }])
            ->select('id', 'uuid', 'code') // specify keys from HealthQuote
            ->whereHas('healthQuoteRequestDetail', function ($query) {
                $query->whereNotNull('chat_initiated_at');
            });
            
        }elseif($modelType == TravelQuote::class){
            $data = TravelQuote::with(['travelQuoteRequestDetail' => function ($query) {
                $query->select('id', 'travel_quote_request_id', 'chat_initiated_at'); // specify keys from travelQuoteRequestDetail
            }])
            ->select('id', 'uuid', 'code') // specify keys from TravelQuote
            ->whereHas('travelQuoteRequestDetail', function ($query) {
                $query->whereNotNull('chat_initiated_at');
            });
        }
        
        if($exportChat)
            return $data->get();
        else
            return inertia('AlfredChat/Index', ['logs' => $data->simplePaginate(15), 'leadStatuses' => QuoteStatus::all(), 'batches' => QuoteBatches::all()]);
    }

    public function processChatFilters(Request $request, $partialQuery, $modelType){

        if (isset($request->quoteId) && $request->quoteId != '') {
            $partialQuery->where('uuid', $request->quoteId);
        }

        if (isset($request->email) && $request->email != '') {
            $partialQuery->where('email', $request->email);
        }

        if (isset($request->mobile_no) && $request->mobile_no != '') {
            $partialQuery->where('mobile_no', $request->mobile_no);
        }

        if (! empty($request->start_date) && ! empty($request->end_date)) {
            $dateFrom = date('Y-m-d 00:00:00', strtotime($request['start_date']));
            $dateTo = date('Y-m-d 23:59:59', strtotime($request['end_date']));
        
            // Determine the correct relationship based on the model type
            $relation = $modelType == HealthQuote::class ? 'healthQuoteRequestDetail' :  ($modelType == CarQuote::class ? 'carQuoteRequestDetail' : 'travelQuoteRequestDetail');
        
            // Apply the whereHas for the determined relation
            $partialQuery->whereHas($relation, function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('chat_initiated_at', [$dateFrom, $dateTo]);
            });
        }

        if (isset($request->transaction_type_id) && $request->transaction_type_id != '') {
            $partialQuery->where('transaction_type_id', $request->transaction_type_id);
        }

        if(isset($request->quote_batch_id) && ! empty($request->quote_batch_id)){
            $partialQuery->whereIn('quote_batch_id', $request->quote_batch_id);
        }

        if (isset($request->quote_status_id) && is_array($request->quote_status_id) && count($request->quote_status_id) > 0) {
            $partialQuery->whereIn('quote_status_id', $request->quote_status_id);
        }

        if (isset($request->payment_status_id) && $request->payment_status_id != '') {
            $partialQuery->where('payment_status_id', $request->payment_status_id);
        }
        
        if(in_array($modelType, [HealthQuote::class, CarQuote::class]) && isset($request->assigment_type) && $request->assigment_type != ''){
            $partialQuery->where('assignment_type', $request->assigment_type);
        }

        if(isset($request->sale_leads) && $request->sale_leads != ''){
            if ($request->sale_leads == quoteTypeCode::yesText) {
                $partialQuery->whereIn('quote_status_id', [QuoteStatusEnum::TransactionApproved, QuoteStatusEnum::PolicyIssued, QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked]);
            }
            if ($request->sale_leads == quoteTypeCode::noText) {
                $partialQuery->whereNotNull('quote_status_id');
            }
        }
      
        if (isset($request->segment_filter) && $request->segment_filter != '') {
            $query = $modelType == HealthQuote::class ? 'hqr' : ($modelType == CarQuote::class ? 'cqr' : 'tqr');
            $quoteTypeId = $modelType == HealthQuote::class ? QuoteTypeId::Health : ($modelType == CarQuote::class ? QuoteTypeId::Car : QuoteTypeId::Travel);

            $modelType::applySegmentFilter($partialQuery, $request->segment_filter, $query, $quoteTypeId);
        }

       

        // mongoDB filters which are missing fallback / message channel
    }

    public function exportChat(Request $request)
    {
        $chat = $this->logs($request, true);

        $fileName = 'alfred_chat_logs_'.Carbon::now()->format('Y-m-d_H-i-s');

        dd($chat->toArray());
        if ($request->report == InstantChatReportsEnum::CONSOLIDATED_REPORT) {
            return Excel::download(new InstantChatConsolidatedExport($chat), $fileName.'.xlsx');
        }
        if ($request->report == InstantChatReportsEnum::DETAILED_REPORT) {
            return Excel::download(new InstantChatDetailedExport($chat), $fileName.'.xlsx');
        }
    }

    public function createPipeline(Request $request, $type)
    {
        $quoteId = null;
        $quoteType = null;
        $pipeline = [];

        if ($request->has('quoteId') && $request->quoteId != null) {
            if (strpos($request->quoteId, '-') !== false) {
                $quote = explode('-', $request->quoteId);
                $quoteId = $quote[1];
            } else {
                $quoteId = $request->quoteId;
            }
        }

        if ($request->get('quoteType')) {
            $quoteType = strtoupper($request->quoteType);
        }

        if (isset($quoteType) && $quoteType != null) {
            $pipeline[] = ['$match' => ['quote_type' => $quoteType]];
        }

        if (isset($quoteId) && $quoteId != null) {
            $pipeline[] = ['$match' => ['quote_id' => $quoteId]];
        }

        if ($request->has('start_date') && $request->start_date != null && $request->has('end_date') && $request->end_date != null) {
            $start_date = Carbon::createFromFormat('Y-m-d', Carbon::parse($request->start_date)->format('Y-m-d'))->startOfDay()->toIso8601String();
            $end_date = Carbon::createFromFormat('Y-m-d', Carbon::parse($request->end_date)->format('Y-m-d'))->endOfDay()->toIso8601String();
            $pipeline[] = ['$match' => ['created_at' => ['$gte' => $start_date, '$lte' => $end_date]]];
        }

        //missing in mongodb
        if (isset($request->transaction_type) && $request->transaction_type != null) {
            $pipeline[] = ['$match' => ['ken_response.quotes.transaction_type' => ['$in' => $request->transaction_type]]];
        }

        // missing in mongodb
        if (isset($request->batch) && $request->batch != null) {
            $pipeline[] = ['$match' => ['ken_response.quotes.batch' => ['$in' => $request->batch]]];
        }
        if (isset($request->lead_status) && $request->lead_status != null) {
            $pipeline[] = ['$match' => ['ken_response.quotes.quoteStatusId' => ['$in' => $request->lead_status]]];
        }
        if (isset($request->payment_status) && $request->payment_status != null) {
            $pipeline[] = ['$match' => ['payment_status' => ['$in' => $request->payment_status]]];
        }
        if (isset($request->sale_leads) && $request->sale_leads === quoteTypeCode::yesText) {
            $approvedStatuses = [
                QuoteStatusEnum::TransactionApproved,
                QuoteStatusEnum::PolicyIssued,
                QuoteStatusEnum::PolicySentToCustomer,
                QuoteStatusEnum::PolicyBooked,
            ];

            $pipeline[] = ['$match' => ['ken_response.quotes.quoteStatusId' => ['$in' => $approvedStatuses]]];
        }
        if (isset($request->fallback) && $request->fallback === quoteTypeCode::yesText) {
            $pipeline[] = [
                '$match' => [
                    '$or' => [
                        ['ken_response.quotes.advisor' => ['$exists' => true, '$ne' => null]], // Check if advisor contact is shared
                        ['fallback' => ['$exists' => true, '$eq' => true]],    // Check if HAPEX contact is shared
                    ],
                ],
            ];
        }
        if (isset($request->message_channel) && $request->message_channel != null) {
            $pipeline[] = ['$match' => ['channel' => $request->message_channel]];
        }
        // missing in mongodb
        if (isset($request->segment) && $request->segment != null) {
            $pipeline[] = ['$match' => ['ken_response.quotes.isSIC' => $request->segment]];
        }
        if (isset($request->mobile_number) && $request->mobile_number != null) {
            $pipeline[] = ['$match' => ['ken_response.quotes.mobile' => $request->mobile_number]];
        }
        if (isset($request->email) && $request->email != null) {
            $pipeline[] = ['$match' => ['ken_response.quotes.email' => $request->email]];
        }

        if ($type === 'chat') {
            $pipeline[] = [
                '$group' => [
                    '_id' => ['quote_id' => '$quote_id', ['$dateToString' => ['timezone' => '+04:00', 'format' => '%Y-%m-%d', 'date' => ['$toDate' => '$created_at']]]],
                    'created_at' => ['$first' => '$created_at'],
                    'role' => ['$first' => '$role'],
                    'msg' => ['$first' => '$msg'],
                    'quote_id' => ['$first' => '$quote_id'],
                    'quote_type' => ['$first' => '$quote_type'],
                    'employee_flag' => ['$first' => '$who_chatted.is_employee'],
                    'email' => ['$first' => '$who_chatted.email'],
                    'user_system' => ['$first' => '$who_chatted.user_agent'],
                    'user_ip_address' => ['$first' => '$who_chatted.ip'],
                    'communication_channel' => ['$first' => '$channel'],
                    'input_tokens_usage' => ['$first' => '$response.usage.prompt_tokens'],
                    'completion_tokens' => ['$first' => '$response.usage.completion_tokens'],
                    'total_tokens' => ['$first' => '$response.usage.total_tokens'],
                    'count' => ['$sum' => 1],
                ],
            ];
        } elseif ($type === 'total') {
            $pipeline[] = [
                '$group' => [
                    '_id' => ['quote_id' => '$quote_id', ['$dateToString' => ['timezone' => '+04:00', 'format' => '%Y-%m-%d', 'date' => ['$toDate' => '$created_at']]]],
                    'quote_type' => ['$first' => '$quote_type'],
                    'quote_id' => ['$first' => '$quote_id'],
                ],
            ];
            $pipeline[] = ['$count' => 'total'];
        } elseif ($request->report == InstantChatReportsEnum::CONSOLIDATED_REPORT) {
            $pipeline[] = [
                '$project' => [
                    'created_at' => 1,
                    'role' => 1,
                    'msg' => 1,
                    'quote_id' => 1,
                    'quote_type' => 1,
                    'employee_flag' => '$who_chatted.is_employee',
                    'email' => '$who_chatted.email',
                    'user_system' => '$who_chatted.user_agent',
                    'user_ip_address' => '$who_chatted.ip',
                    'communication_channel' => '$channel',
                    'input_tokens_usage' => '$response.usage.prompt_tokens',
                    'completion_tokens' => '$response.usage.completion_tokens',
                    'total_tokens' => '$response.usage.total_tokens',
                ],
            ];
        } elseif ($request->report == InstantChatReportsEnum::DETAILED_REPORT) {
            $pipeline[] = [
                '$group' => [
                    '_id' => '$quote_id',
                    'quote_type' => ['$last' => '$quote_type'],
                    'date_of_first_interaction' => ['$min' => '$created_at'],
                    'communication_channels' => ['$addToSet' => '$channel'],
                    'batch' => [
                        '$last' => [
                            '$cond' => [
                                ['$$ROOT.role', 'USER'],
                                ['$last' => '$ken_reponse.quotes.batch'],
                                null,
                            ],
                        ],
                    ],
                    'transaction' => [
                        '$last' => [
                            '$cond' => [
                                ['$$ROOT.role', 'USER'],
                                ['$last' => '$ken_reponse.quotes.transaction_type'],
                                null,
                            ],
                        ],
                    ],
                    'segment' => [
                        '$last' => [
                            '$cond' => [
                                ['$$ROOT.role', 'USER'],
                                ['$last' => '$ken_reponse.quotes.segment'],
                                null,
                            ],
                        ],
                    ],
                    'payment_status' => [
                        '$last' => [
                            '$cond' => [
                                ['$$ROOT.role', 'USER'],
                                ['$first' => '$ken_reponse.quotes.paymentStatus'],
                                null,
                            ],
                        ],
                    ],
                    'provider_name' => [
                        '$last' => [
                            '$cond' => [
                                ['$$ROOT.role', 'USER'],
                                ['$last' => '$ken_reponse.quotes.providerName'],
                                null,
                            ],
                        ],
                    ],
                    'customer_interactions' => [
                        '$sum' => [
                            '$cond' => [
                                ['$eq' => ['$role', 'USER']],
                                1,
                                0,
                            ],
                        ],
                    ],
                    'ai_interactions' => [
                        '$sum' => [
                            '$cond' => [
                                ['$eq' => ['$role', 'AI']],
                                1,
                                0,
                            ],
                        ],
                    ],
                    'total_ai_interactions' => [
                        '$sum' => [
                            '$cond' => [
                                ['$in' => ['$role', ['AI', 'USER']]],
                                1,
                                0,
                            ],
                        ],
                    ],
                    'fallbacks' => ['$sum' => ['$cond' => [['fallback', true], 1, 0]]],
                    'payment_status' => ['$last' => '$ken_reponse.quotes.paymentStatus'],
                    'sale_leads' => [
                        '$max' => [
                            '$cond' => [
                                [
                                    '$and' => [
                                        ['$eq' => ['$role', 'USER']],
                                        ['$in' => [
                                            '$ken_reponse.quotes.quoteStatusId',
                                            [
                                                QuoteStatusEnum::TransactionApproved,
                                                QuoteStatusEnum::PolicyIssued,
                                                QuoteStatusEnum::PolicySentToCustomer,
                                                QuoteStatusEnum::PolicyBooked,
                                            ],
                                        ]],
                                    ],
                                ],
                                'Yes',
                                'No',
                            ],
                        ],
                    ],
                    'plan_type' => [
                        '$last' => [
                            '$cond' => [
                                ['$$ROOT.role', 'USER'],
                                ['$last' => '$ken_reponse.quotes.plan_type'],
                                null,
                            ],
                        ],
                    ],
                    'plan_name' => [
                        '$last' => [
                            '$cond' => [
                                ['$$ROOT.role', 'USER'],
                                ['$last' => '$ken_reponse.quotes.plan_name'],
                                null,
                            ],
                        ],
                    ],
                    'price' => [
                        '$last' => [
                            '$cond' => [
                                ['$$ROOT.role', 'USER'],
                                ['$last' => '$ken_reponse.quotes.price'],
                                null,
                            ],
                        ],
                    ],
                    'payment_date' => [
                        '$last' => [
                            '$cond' => [
                                ['$$ROOT.role', 'USER'],
                                ['$last' => '$ken_reponse.quotes.payment_date'],
                                null,
                            ],
                        ],
                    ],
                    'ep_purchased' => [
                        '$last' => [
                            '$cond' => [
                                ['$$ROOT.role', 'USER'],
                                ['$last' => '$ken_reponse.quotes.ep_purchased'],
                                null,
                            ],
                        ],
                    ],
                ],
            ];

            // $pipeline[] = [
            //     '$project' => [
            //         'quote_type' => 1,
            //         'quote_id' => 1,
            //         'created_at' => 1,
            //         'communication_channel' => '$channel',
            //         'batch' =>   ['$arrayElemAt' => ['$ken_response.quotes.batch', 0]],// missing
            //         'transaction' =>   ['$arrayElemAt' => ['$ken_response.quotes.transaction', 0]],
            //         'segment' =>   ['$arrayElemAt' => ['$ken_response.quotes.segment', 0]],
            //         'ai_interactions' =>   ['$arrayElemAt' => ['$ken_response.quotes.ai_interactions', 0]],
            //         'fallbacks' =>   ['$arrayElemAt' => ['$ken_response.quotes.fallback_counts', 0]],
            //         'payment_status' =>   ['$arrayElemAt' => ['$ken_response.quotes.paymentStatus', 0]],
            //         'sale_leads' =>   ['$arrayElemAt' => ['$ken_response.quotes.sale_leads', 0]],
            //         'provider_name' =>   ['$arrayElemAt' => ['$ken_response.quotes.currentlyInsuredWith', 0]],
            //         'plan_type' =>   ['$arrayElemAt' => ['$ken_response.quotes.plan_type', 0]],
            //         'plan_name' =>   ['$arrayElemAt' => ['$ken_response.quotes.plan_name', 0]],
            //         'price' =>   ['$arrayElemAt' => ['$ken_response.quotes.price', 0]],
            //         'payment_date' =>   ['$arrayElemAt' => ['$ken_response.quotes.payment_date', 0]], // missing
            //         'ep_purchased' =>   ['$arrayElemAt' => ['$ken_response.quotes.ep_purchased', 0]], // missing
            //     ],
            // ];
        }

        return $pipeline;
    }

}
