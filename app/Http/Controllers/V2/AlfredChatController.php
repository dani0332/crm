<?php

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\AlfredChatRequest;
use App\Models\AlfredChat;
use App\Models\QuoteBatches;
use App\Models\QuoteStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
        $dateFrom = Carbon::createFromFormat('Y-m-d', $request->created_at)->startOfDay()->toIso8601String();
        $dateTo = Carbon::createFromFormat('Y-m-d', $request->created_at)->endOfDay()->toIso8601String();

        $chat = AlfredChat::where('quote_id', $request->quoteId)
            ->where('quote_type', $request->quoteType)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
        // ->select('role', 'msg', 'created_at')
            ->get();

        if ($chat->isEmpty()) {
            return response()->json(['message' => 'No chat available']);
        }

        return response()->json(['data' => $chat]);
    }

    public function logs(Request $request)
    {

        $quoteId = null;
        $quoteType = null;
        if ($request->has('quoteId')) {
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

        if (isset($quoteType)) {
            $totalPipeline = [
                ['$match' => ['quote_type' => $quoteType]],
            ];
        }

        if (isset($quoteId)) {
            $totalPipeline = [
                ['$match' => ['quote_id' => $quoteId]],
            ];
        }

        // Apply date range filter if provided
        if ($request->has('start_date') && $request->has('end_date')) {
            $start_date = Carbon::createFromFormat('Y-m-d', $request->start_date)->startOfDay()->toIso8601String();
            $end_date = Carbon::createFromFormat('Y-m-d', $request->end_date)->endOfDay()->toIso8601String();

            $totalPipeline[] = [
                '$match' => [
                    'created_at' => ['$gte' => $start_date, '$lte' => $end_date],
                ],
            ];
        }

        // Add a $group stage to count total documents
        $totalPipeline[] = ['$group' => [
            '_id' => ['quote_id' => '$quote_id',
                ['$dateToString' => ['timezone' => '+04:00', 'format' => '%Y-%m-%d',
                    'date' => ['$toDate' => '$created_at']]]],
            'quote_type' => ['$first' => '$quote_type'],
            'quote_id' => ['$first' => '$quote_id'],
        ],
        ];
        $totalPipeline[] = ['$count' => 'total'];

        // Execute the aggregation pipeline to get the total count
        $totalDocuments = AlfredChat::raw(fn ($collection) => $collection->aggregate($totalPipeline))->toArray();

        $totalDocumentsCount = empty($totalDocuments) ? 0 : $totalDocuments[0]['total'];

        // Define pagination parameters
        $perPage = 15; // Or any number of documents per page
        $page = $request->has('page') ? max(1, (int) $request->page) : 1;
        $skip = ($page - 1) * $perPage;

        if (isset($quoteType)) {
            $chatPipeline = [
                ['$match' => ['quote_type' => $quoteType]],
            ];
        }

        // Define the aggregation pipeline for fetching paginated chat records
        if (isset($quoteId)) {
            $chatPipeline = [
                ['$match' => ['quote_id' => $quoteId]],
            ];
        }

        // Apply date range filter if provided
        if ($request->has('start_date') && $request->has('end_date')) {
            $start_date = Carbon::createFromFormat('Y-m-d', $request->start_date)->startOfDay()->toIso8601String();
            $end_date = Carbon::createFromFormat('Y-m-d', $request->end_date)->endOfDay()->toIso8601String();
            $chatPipeline[] = [
                '$match' => [
                    'created_at' => ['$gte' => $start_date, '$lte' => $end_date],
                ],
            ];
        }

        // missing in db
        if (isset($request->transaction_type) && $request->transaction_type != '') {
            $chatPipeline = [
                ['$match' => ['ken_response.quotes.transaction_type' => ['$in' => $request->transaction_type]]],
            ];
        }

        //  // missing in db
        if (isset($request->batch) && $request->batch != '') {
            $chatPipeline = [
                ['$match' => ['ken_response.quotes.batch' => ['$in' => $request->batch]]],
            ];
        }

        // // missing in db
        if (isset($request->lead_status) && $request->lead_status != '') {
            $chatPipeline = [
                ['$match' => ['ken_response.quotes.lead_status' => ['$in' => $request->lead_status]]],
            ];
        }

        if (isset($request->payment_status) && $request->payment_status != '') {
            $chatPipeline = [
                ['$match' => ['payment_status' => ['$in' => $request->payment_status]]],
            ];

        }

        if (isset($request->sale_leads) && $request->sale_leads != '') {
            $chatPipeline = [
                ['$match' => ['ken_response.quotes.sale_leads' => $request->sale_leads]],
            ];
        }

        if (isset($request->fallback) && $request->fallback != '') {
            $chatPipeline = [
                ['$match' => ['fallback' => $request->fallback == 'yes' ? true : false]],
            ];
        }

        if (isset($request->message_channel) && $request->message_channel != '') {
            $chatPipeline = [
                ['$match' => ['channel' => $request->message_channel]],
            ];
        }

        // missing in db
        if (isset($request->segment) && $request->segment != '') {
            $chatPipeline = [
                ['$match' => ['ken_response.quotes.isSIC' => $request->segment]],
            ];
        }

        if (isset($request->mobile_number) && $request->mobile_number != '') {
            $chatPipeline = [
                ['$match' => ['ken_response.quotes.mobile' => $request->mobile_number]],
            ];

        }

        if (isset($request->email) && $request->email != '') {
            $chatPipeline = [
                ['$match' => ['ken_response.quotes.email' => $request->email]],
            ];
        }

        if (isset($request->report) && $request->report != '') {
            $chatPipeline = [
                ['$match' => ['report' => $request->report]],
            ];
        }

        // Add $group, $sort, $skip, and $limit stages for pagination
        $chatPipeline[] = [
            '$group' => [
                '_id' => ['quote_id' => '$quote_id',
                    ['$dateToString' => ['timezone' => '+04:00', 'format' => '%Y-%m-%d',
                        'date' => ['$toDate' => '$created_at']]]],
                'created_at' => ['$first' => '$created_at'],
                'role' => ['$first' => '$role'],
                'msg' => ['$first' => '$msg'],
                'quote_id' => ['$first' => '$quote_id'],
                'quote_type' => ['$first' => '$quote_type'],
                'count' => ['$sum' => 1],
            ],
        ];

        $chatPipeline[] = ['$sort' => ['created_at' => -1]];
        $chatPipeline[] = ['$skip' => $skip];
        $chatPipeline[] = ['$limit' => $perPage];

        // Execute the aggregation pipeline to fetch paginated chat records
        $chat = AlfredChat::raw(fn ($collection) => $collection->aggregate($chatPipeline));

        // dd($chat);
        // Calculate pagination indices
        $startIndex = ($page - 1) * $perPage;
        $endIndex = max($startIndex + $perPage, $totalDocumentsCount);
        $prevPage = $page > 1 ? $page - 1 : null;
        $nextPage = $endIndex <= $totalDocumentsCount ? $page + 1 : null;

        // Create pagination object
        $pagination = [
            'data' => $chat,
            'current_page' => $page,

            'prev_page_url' => $prevPage ? $request->url().'?page='.$prevPage.
            ($request->start_date ? '&start_date='.$request->start_date : '').
            ($request->end_date ? '&end_date='.$request->end_date : '').
            ($request->quoteType ? '&quoteType='.$request->quoteType : '').
            ($request->quoteId ? '&quoteId='.$request->quoteId : '')
            : null,

            'next_page_url' => $nextPage ? $request->url().'?page='.$nextPage.
            ($request->start_date ? '&start_date='.$request->start_date : '').
            ($request->end_date ? '&end_date='.$request->end_date : '').
            ($request->quoteType ? '&quoteType='.$request->quoteType : '').
            ($request->quoteId ? '&quoteId='.$request->quoteId : '')
            : null,

            'from' => $startIndex + 1,
            'to' => $endIndex,
        ];

        // Now you can pass these variables to your pagination component
        return inertia('AlfredChat/Index', ['logs' => $pagination, 'leadStatuses' => QuoteStatus::all(), 'batches' => QuoteBatches::all()]);
    }

    public function extractChatReport(Request $request)
    {

    }
}
