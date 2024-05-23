<?php

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Exports\EmbeddedProductReport;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmbeddedProducDocumentRequest;
use App\Http\Requests\EmbeddedProductRequest;
use App\Models\EmbeddedProduct;
use App\Repositories\EmbeddedProductRepository;
use App\Services\QuoteSyncService;
use Illuminate\Http\Request;
use App\Enums\QuoteTypeId;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use App\Models\QuoteSync;
use App\Traits\PersonalQuoteSyncTrait;

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
        if(isset($filters['quote_type']) && in_array($filters['quote_type'], [QuotetypeId::Corpline, QuotetypeId::GroupMedical])) {
            $filters['quote_type'] = QuotetypeId::Business;
        }
        
        $dataset = $quoteSyncService->getData($filters);
        $quotetypeOptions = QuoteTypeId::getOptions();
        if(isset($quotetypeOptions[QuotetypeId::Business])) {
            unset($quotetypeOptions[QuotetypeId::Business]);
        }

        return inertia('QuoteSync/Index', [
            'logs' => $dataset,
            'quote_types' => $quotetypeOptions,
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

        if($personalQuote) {
            $personalQuoteDetails = PersonalQuoteDetail::where('personal_quote_id', $personalQuote->id)->first();
        }

        $modelClassName = $this->getQuoteType($quoteSync->quote_type_id);
        $sourceQuote = $modelClassName::where('uuid', $quoteSync->quote_uuid)->first();
        $sourceQuoteDetails = null;
        if($sourceQuote) {
            $sourceQuoteDetails = $this->getQuoteDetailRecord($quoteSync->quote_type_id, $sourceQuote->id);
        }

        $quoteType = QuoteTypeId::getOptions();
        return inertia('QuoteSync/Show', [
            'quote_type' => $quoteType[$quoteSync->quote_type_id],
            'quote_sync' => $quoteSync,
            'personal_quote' => !empty($personalQuote) ? $personalQuote->toArray() : null,
            'personal_quote_details' => !empty($personalQuoteDetails) ? $personalQuoteDetails->toArray() : null,
            'source_quote' => !empty($sourceQuote) ? $sourceQuote->toArray() : null,
            'source_quote_details' => !empty($sourceQuoteDetails) ? $sourceQuoteDetails->toArray() : null,
        ]);
    }
}
