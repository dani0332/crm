<?php

namespace App\Services;

use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Models\CycleQuote;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CycleQuoteService extends BaseService
{
    public function processManualLeadAssignment($request): array
    {
        if ($request->selectTmLeadId == '' || $request->selectTmLeadId == null) {
            $leadsIds = array_map('intval', explode(',', trim($request->entityId, ',')));
        } else {
            $leadsIds = array_map('intval', explode(',', trim($request->selectTmLeadId, ',')));
        }
        $userId = (int) $request->assigned_to_id_new;
        Log::info('Leads ids to assign: '.json_encode($leadsIds));
        $result = [];

        foreach ($leadsIds as $leadId) {
            if (in_array(quoteTypeCode::Cycle, newUi())) {
                $lead = PersonalQuote::findOrfail($leadId);
            } else {
                $lead = CycleQuote::findOrfail($leadId);
            }

            if ($lead) {
                $lead->advisor_id = $userId;
                $lead->save();
                $this->updateChildRecord($lead->id);
            }
        }

        return $result;
    }

    public function getEntityPlain($id)
    {
        return PersonalQuote::where('id', $id)->first();
    }

    public function validateRequest($request)
    {
        $userId = $request->assigned_to_id_new;
        $leadsIds = $request->selectTmLeadId == null || $request->selectTmLeadId == '' ? $request->entityId : $request->selectTmLeadId;
        if ($leadsIds == '' || $leadsIds == null) {
            return 'Please select lead(s) to assign';
        }
        if (substr($leadsIds, 0, 1) == ',') {
            $leadsIds = substr($leadsIds, 1);
        }
        $leadsIds = array_map('intval', explode(',', $leadsIds));

        foreach ($leadsIds as $leadId) {
            $entity = $this->getEntityPlain($leadId);
            if ($entity->quote_status_id == QuoteStatusEnum::TransactionApproved) {
                return 'One of the selected lead is in Transaction Approved state. Please unselect the lead and try again.';
            }
        }
        if ($userId == '' || $userId == null) {
            return 'Please select user to assign leads';
        }

        return 'true';
    }

    public function createDetailEntity($id)
    {
        return PersonalQuoteDetail::create([
            'personal_quote_id' => $id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function updateChildRecord($id)
    {
        $childRecord = PersonalQuoteDetail::where('personal_quote_id', $id)->first();

        if (empty($childRecord)) {
            $childRecord = $this->createDetailEntity($id);
        }

        $childRecord->advisor_assigned_by_id = auth()->user()->id;
        $childRecord->advisor_assigned_date = Carbon::now();
        $childRecord->save();
    }
}
