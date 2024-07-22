<?php

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteSyncStatus;
use App\Enums\QuoteTypeId;
use App\Http\Controllers\Controller;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use App\Models\QuoteSync;
use App\Services\QuoteSyncService;
use App\Traits\PersonalQuoteSyncTrait;
use Illuminate\Http\Request;

class QuoteSyncController extends Controller
{
    use PersonalQuoteSyncTrait;

    public function __construct()
    {
        $this->middleware('permission:'.PermissionsEnum::QUOTE_SYNC_LOGS);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index(Request $request, QuoteSyncService $quoteSyncService)
    {
        $filters = $request->all();
        if (isset($filters['quote_type']) && in_array($filters['quote_type'], [QuotetypeId::Corpline, QuotetypeId::GroupMedical])) {
            $filters['quote_type'] = QuotetypeId::Business;
        }

        $dataset = $quoteSyncService->getData($filters);
        $quotetypeOptions = QuoteTypeId::getOptions();
        if (isset($quotetypeOptions[QuotetypeId::Business])) {
            unset($quotetypeOptions[QuotetypeId::Business]);
        }
        $quoteSyncStatusOptions = QuoteSyncStatus::getOptions();

        return inertia('QuoteSync/Index', [
            'logs' => $dataset['dataset'],
            'count' => $dataset['count'] ?? 0,
            'quote_types' => $quotetypeOptions,
            'quote_sync_status' => $quoteSyncStatusOptions,
        ]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(QuoteSync $quoteSync)
    {
        $personalQuote = PersonalQuote::where('uuid', $quoteSync->quote_uuid)
            ->where('quote_type_id', $quoteSync->quote_type_id)
            ->first();
        $personalQuoteDetails = null;

        if ($personalQuote) {
            $personalQuoteDetails = PersonalQuoteDetail::where('personal_quote_id', $personalQuote->id)->first();
        }

        $modelClassName = $this->getQuoteType($quoteSync->quote_type_id);
        $sourceQuote = $modelClassName::where('uuid', $quoteSync->quote_uuid)->first();
        $sourceQuoteDetails = null;
        if ($sourceQuote) {
            $sourceQuoteDetails = $this->getQuoteDetailRecord($quoteSync->quote_type_id, $sourceQuote->id);
        }

        $quoteType = QuoteTypeId::getOptions();

        return inertia('QuoteSync/Show', [
            'quote_type' => $quoteType[$quoteSync->quote_type_id],
            'quote_sync' => $quoteSync,
            'personal_quote' => ! empty($personalQuote) ? $personalQuote->toArray() : null,
            'personal_quote_details' => ! empty($personalQuoteDetails) ? $personalQuoteDetails->toArray() : null,
            'source_quote' => ! empty($sourceQuote) ? $sourceQuote->toArray() : null,
            'source_quote_details' => ! empty($sourceQuoteDetails) ? $sourceQuoteDetails->toArray() : null,
        ]);
    }

    public function edit(QuoteSync $quoteSync)
    {
        $quoteSyncStatusOptions = QuoteSyncStatus::getOptions();

        return inertia('QuoteSync/Form', [
            'quote_sync' => $quoteSync,
            'quote_sync_status' => $quoteSyncStatusOptions,
        ]);
    }

    public function update(Request $request, QuoteSync $quoteSync, QuoteSyncService $quoteSyncService)
    {
        $quoteSync->update($request->all());

        if (isset($request->sync_followed_entries) && $request->sync_followed_entries == 1) {
            $quoteSyncService->addFollowedEntriesForSyncing($quoteSync);
        }

        return redirect()->route('admin.quotesync.show', $quoteSync->id)->with('message', 'Quote Sync updated successfully');
    }

    public function addStuckEntriesForSyncing(QuoteSyncService $quoteSyncService)
    {
        $quoteSyncService->addEntriesForReSyncing(QuoteSyncStatus::INPROGRESS);
    }

    public function addFailedEntriesForSyncing(QuoteSyncService $quoteSyncService)
    {
        $quoteSyncService->addEntriesForReSyncing(QuoteSyncStatus::FAILED);
    }
}
