<?php

namespace App\Services;

use App\Enums\tmInsuranceTypeCode;
use App\Enums\tmLeadStatusCode;
use App\Models\TmInsuranceType;
use App\Models\TmLead;
use App\Models\TmLeadContactInformation;
use App\Models\TmLeadStatus;
use Auth;
use Illuminate\Http\Request;

class TMLeadsService
{
    public function tmLeadsCreateUpdate(Request $request, $type, $tmLeadID)
    {
        $tmInsuranceTypeCode = TmInsuranceType::where('id', '=', $request->tm_insurance_types_id)->value('code');

        if ($type == 'create') {
            $tmLead = new TmLead;

            $tmLeadStatusEnum = tmLeadStatusCode::NewLead;
            $tmLeadStatusId = TmLeadStatus::where('code', '=', $tmLeadStatusEnum)->value('id');
            $tmLead->tm_lead_statuses_id = $tmLeadStatusId;
        }
        if ($type == 'update') {
            $tmLead = TmLead::find($tmLeadID);
        }

        $tmLead->customer_name = $request->customer_name;
        $tmLead->phone_number = $request->phone_number;
        $tmLead->email_address = strtolower(trim(ltrim(rtrim($request->email_address))));
        $tmLead->enquiry_date = $request->enquiry_date;
        $tmLead->allocation_date = $request->allocation_date;

        if ($type == 'create') {
            $tmLead->created_by_id = Auth::user()->id;
            $tmLead->modified_by_id = Auth::user()->id;

            if (Auth::user()->hasRole('TM_ADVISOR')) {
                $tmLead->assigned_to_id = Auth::user()->id;
            } else {
                $tmLead->assigned_to_id = null;
            }
        }
        if ($type == 'update') {
            $tmLead->modified_by_id = Auth::user()->id;
        }
        $tmLead->tm_lead_types_id = $request->tm_lead_types_id;
        $tmLead->tm_insurance_types_id = $request->tm_insurance_types_id;

        if ($tmInsuranceTypeCode == tmInsuranceTypeCode::Car) {
            $tmLead->year_of_manufacture = $request->year_of_manufacture;
            $tmLead->car_value = floatval(preg_replace('/[^\d.]/', '', $request->car_value));
            $tmLead->car_model_id = $request->car_model_id;
            $tmLead->car_make_id = $request->car_make_id;
            $tmLead->nationality_id = $request->nationality_id;
            $tmLead->years_of_driving_id = $request->years_of_driving_id;
            $tmLead->emirates_of_registration_id = $request->emirates_of_registration_id;
            $tmLead->car_type_insurance_id = $request->car_type_insurance_id;
        }

        if ($tmInsuranceTypeCode != tmInsuranceTypeCode::Car) {
            $tmLead->year_of_manufacture = null;
            $tmLead->car_value = null;
            $tmLead->car_model_id = null;
            $tmLead->car_make_id = null;
            $tmLead->nationality_id = null;
            $tmLead->years_of_driving_id = null;
            $tmLead->emirates_of_registration_id = null;
            $tmLead->car_type_insurance_id = null;
        }
        if ($tmInsuranceTypeCode == tmInsuranceTypeCode::Car || $tmInsuranceTypeCode == tmInsuranceTypeCode::Bike
        || $tmInsuranceTypeCode == tmInsuranceTypeCode::Life || $tmInsuranceTypeCode == tmInsuranceTypeCode::Health) {
            $tmLead->dob = $request->dob;
        } else {
            $tmLead->dob = null;
        }
        $tmLead->save();

        $updateTmLead = TmLead::find($tmLead->id);
        $updateTmLead->cdb_id = 'TM-'.$tmLead->id;
        $updateTmLead->save();

        $tmLead->additionalInformation()->delete();
        if ($request->has('phones') && $request->has('emails')) {
            $phones = $request->phones;
            $emails = $request->emails;
            for ($i = 0; $i < count($phones); $i++) {
                if (empty($phones[$i]) && empty($emails[$i])) {
                    continue;
                }
                $model = new TmLeadContactInformation;
                $model->phone_number = $phones[$i];
                $model->email_address = $emails[$i];
                $model->tm_lead_id = $tmLead->id;
                $model->created_by = Auth::user()->id;
                $model->updated_by = Auth::user()->id;
                $model->save();
            }
        }

        return $tmLead->id;
    }

    public function tmLeadStatusNotesUpdate(Request $request)
    {
        $tmLeadStatusCode = TmLeadStatus::where('id', '=', $request->tm_lead_statuses_id)->value('code');

        $tmLead = TmLead::find($request->tmLeadId);

        if ($tmLeadStatusCode == tmLeadStatusCode::NoAnswer || $tmLeadStatusCode == tmLeadStatusCode::SwitchedOff) {
            if ($request->no_answer_count < '3' && $request->next_followup_date != ''
            && $request->next_followup_date != $tmLead->next_followup_date) {
                $no_answer_count = $tmLead->no_answer_count + 1;
                $tmLead->no_answer_count = $no_answer_count;
                if ($no_answer_count == 3) {
                    $tmLeadStatusEnum = tmLeadStatusCode::NotContactablePE;
                    $tmLeadStatusId = TmLeadStatus::where('code', '=', $tmLeadStatusEnum)->value('id');
                    $tmLead->tm_lead_statuses_id = $tmLeadStatusId;
                    $tmLead->next_followup_date = null;
                } else {
                    $tmLead->tm_lead_statuses_id = $request->tm_lead_statuses_id;
                    $tmLead->next_followup_date = $request->next_followup_date;
                }
            }
        } else {
            $tmLead->tm_lead_statuses_id = $request->tm_lead_statuses_id;

            if ($tmLeadStatusCode == tmLeadStatusCode::NotContactablePE || $tmLeadStatusCode == tmLeadStatusCode::CarSold
            || $tmLeadStatusCode == tmLeadStatusCode::NotEligible || $tmLeadStatusCode == tmLeadStatusCode::NotInterested
            || $tmLeadStatusCode == tmLeadStatusCode::PurchasedBeforeFirstCall || $tmLeadStatusCode == tmLeadStatusCode::PurchasedFromCompetitor
            || $tmLeadStatusCode == tmLeadStatusCode::RevivedByNewBusiness || $tmLeadStatusCode == tmLeadStatusCode::RevivedByRenewals
            || $tmLeadStatusCode == tmLeadStatusCode::WrongNumber || $tmLeadStatusCode == tmLeadStatusCode::DONOTCALL
            || $tmLeadStatusCode == tmLeadStatusCode::Duplicate || $tmLeadStatusCode == tmLeadStatusCode::Revived
            || $tmLeadStatusCode == tmLeadStatusCode::Recycled || ($tmLeadStatusCode == tmLeadStatusCode::NewLead && $tmLead->next_followup_date == '')) {
                $tmLead->next_followup_date = null;
            } else {
                $tmLead->next_followup_date = $request->next_followup_date;
            }
        }

        $tmLead->notes = $request->notes;
        $tmLead->modified_by_id = Auth::user()->id;
        $tmLead->save();

        return $tmLead->id;
    }

    public function tmLeadsUpdateAssignedTo(Request $request)
    {
        $assignedToUserIdNew = $request->assigned_to_id_new;
        $tmLeadsIds = $request->selectTmLeadId;

        if (substr($tmLeadsIds, 0, 1) == ',') {
            $tmLeadsIds = substr($tmLeadsIds, 1);
        }

        $tmLeadsIds = array_map('intval', explode(',', $tmLeadsIds));
        foreach ($tmLeadsIds as $tmLeadsId) {
            $updateTmLead = TmLead::find($tmLeadsId);
            $updateTmLead->assigned_to_id = $assignedToUserIdNew;
            $updateTmLead->save();
        }

        return $assignedToUserIdNew;
    }

    public function tmLeadsGetPrioritizeLead($currentUserID)
    {
        $prioritizeLeads = TmLead::select('tm_leads.id as tmLeadId')
            ->leftjoin('tm_lead_statuses', 'tm_leads.tm_lead_statuses_id', 'tm_lead_statuses.id')
            ->whereNotIn('tm_lead_statuses.code', ['NotContactablePE', 'CarSold', 'NotEligible', 'NotInterested', 'PurchasedBeforeFirstCall', 'PurchasedFromCompetitor', 'WrongNumber', 'DONOTCALL', 'Duplicate', 'Recycled', 'Revived', 'RevivedByNewBusiness', 'RevivedByRenewals'])
            ->whereRaw('tm_leads.is_deleted=0 AND (tm_leads.next_followup_date IS NULL OR tm_leads.next_followup_date < now()) AND tm_leads.assigned_to_id='.$currentUserID)
            ->orderByRaw('tm_leads.next_followup_date IS NULL, tm_leads.next_followup_date, tm_leads.created_at')->limit(1)->get();
        $prioritizeLeadId = '';
        if (! empty($prioritizeLeads)) {
            foreach ($prioritizeLeads as $prioritizeLead) {
                $prioritizeLeadId = $prioritizeLead->tmLeadId;
            }
        } else {
            $prioritizeLeadId = '';
        }

        return $prioritizeLeadId;
    }
}
