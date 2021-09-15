<?php

namespace App\Services;
use App\Models\TmLead;
use App\Models\TmInsuranceType;
use App\Models\TmCallStatus;
use App\Models\TmLeadStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Config;
use Illuminate\Http\Request;
use App\Enums\tmInsuranceTypeCode;
use App\Enums\tmLeadStatusCode;
use Auth;

class TMLeadsService
{
    public function tmLeadsCreateUpdate(Request $request, $type, $tmLeadID)
    {
        $tmInsuranceTypeCode = TmInsuranceType::where('id', '=', $request->tm_insurance_types_id)->value('code');

        if($type == "create") {
            $tmLead = new TmLead();

            $tmLeadStatusEnum = tmLeadStatusCode::NewLead;
            $tmLeadStatusId = TmLeadStatus::where('code', '=', $tmLeadStatusEnum)->value('id');
            $tmLead->tm_lead_statuses_id = $tmLeadStatusId;
        }
        if($type == "update") {
            $tmLead = TmLead::find($tmLeadID);
        }

        $tmLead->customer_name = $request->customer_name;
        $tmLead->phone_number = $request->phone_number;
        $tmLead->email_address = strtolower(trim(ltrim(rtrim($request->email_address))));
        $tmLead->enquiry_date = $request->enquiry_date;
        $tmLead->allocation_date = $request->allocation_date;

        if($type == "create") {
            $tmLead->created_by_id = Auth::user()->id;
            $tmLead->modified_by_id = Auth::user()->id;

            if(Auth::user()->hasRole('TM_ADVISOR')) {
                $tmLead->assigned_to_id = Auth::user()->id;
            }
            else {
                $tmLead->assigned_to_id = NULL;
            }
        }
        if($type == "update") {
            $tmLead->modified_by_id = Auth::user()->id;
        }
        $tmLead->tm_lead_types_id = $request->tm_lead_types_id;
        $tmLead->tm_insurance_types_id = $request->tm_insurance_types_id;

        if($tmInsuranceTypeCode == tmInsuranceTypeCode::Car) {
            $tmLead->dob = $request->dob;
            $tmLead->year_of_manufacture = $request->year_of_manufacture;
            $tmLead->car_value = $request->car_value;
            $tmLead->car_model_id = $request->car_model_id;
            $tmLead->car_make_id = $request->car_make_id;
            $tmLead->nationality_id = $request->nationality_id;
            $tmLead->years_of_driving_id = $request->years_of_driving_id;
            $tmLead->emirates_of_registration_id = $request->emirates_of_registration_id;
            $tmLead->car_type_insurance_id = $request->car_type_insurance_id;
        }

        if($tmInsuranceTypeCode != tmInsuranceTypeCode::Car) {
            $tmLead->dob = NULL;
            $tmLead->year_of_manufacture = NULL;
            $tmLead->car_value = NULL;
            $tmLead->car_model_id = NULL;
            $tmLead->car_make_id = NULL;
            $tmLead->nationality_id = NULL;
            $tmLead->years_of_driving_id = NULL;
            $tmLead->emirates_of_registration_id = NULL;
            $tmLead->car_type_insurance_id = NULL;
        }

        $tmLead->save();

        $updateTmLead = TmLead::find($tmLead->id);
        $updateTmLead->cdb_id = "TM-".$tmLead->id;
        $updateTmLead->save();

        return $tmLead->id;
    }

    public function tmLeadStatusNotesUpdate(Request $request)
    {
        $tmLeadStatusCode = TmLeadStatus::where('id', '=', $request->tm_lead_statuses_id)->value('code');

        $tmLead = TmLead::find($request->tmLeadId);

        if(($tmLeadStatusCode == tmLeadStatusCode::NoAnswer || $tmLeadStatusCode == tmLeadStatusCode::SwitchedOff)
        && $request->no_answer_count < "3" && $request->next_followup_date != ""
        && $request->next_followup_date != $tmLead->next_followup_date) {
            $no_answer_count = $tmLead->no_answer_count + 1;
            $tmLead->no_answer_count = $no_answer_count;
            if($no_answer_count == 3) {

                $tmLeadStatusEnum = tmLeadStatusCode::NotContactablePE;
                $tmLeadStatusId = TmLeadStatus::where('code', '=', $tmLeadStatusEnum)->value('id');
                $tmLead->tm_lead_statuses_id = $tmLeadStatusId;
            }
            else {
                $tmLead->tm_lead_statuses_id = $request->tm_lead_statuses_id;
            }
        }
        else {

            $CurrentTmLeadStatusCode = TmLeadStatus::where('id', '=', $tmLead->tm_lead_statuses_id)->value('code');

            if( ($CurrentTmLeadStatusCode == tmLeadStatusCode::NoAnswer || $CurrentTmLeadStatusCode == tmLeadStatusCode::SwitchedOff
            || $CurrentTmLeadStatusCode == tmLeadStatusCode::PipelineNoInfo || $CurrentTmLeadStatusCode == tmLeadStatusCode::PipelineImmediate || $tmLeadStatusCode == tmLeadStatusCode::PipelineFuture
            || $CurrentTmLeadStatusCode == tmLeadStatusCode::DealingWithAnAdvisor)
            && ($tmLeadStatusCode != tmLeadStatusCode::NoAnswer && $tmLeadStatusCode != tmLeadStatusCode::SwitchedOff
            && $tmLeadStatusCode != tmLeadStatusCode::PipelineNoInfo && $tmLeadStatusCode != tmLeadStatusCode::PipelineImmediate && $tmLeadStatusCode != tmLeadStatusCode::PipelineFuture
            || $tmLeadStatusCode != tmLeadStatusCode::DealingWithAnAdvisor) ) {
                $tmLead->next_followup_date = NULL;
            }

            $tmLead->tm_lead_statuses_id = $request->tm_lead_statuses_id;
        }

        if($request->next_followup_date != ""
            && $request->next_followup_date != $tmLead->next_followup_date
            && ($tmLeadStatusCode == tmLeadStatusCode::NoAnswer || $tmLeadStatusCode == tmLeadStatusCode::SwitchedOff
            || $tmLeadStatusCode == tmLeadStatusCode::PipelineNoInfo || $tmLeadStatusCode == tmLeadStatusCode::PipelineImmediate || $tmLeadStatusCode == tmLeadStatusCode::PipelineFuture
            || $tmLeadStatusCode == tmLeadStatusCode::DealingWithAnAdvisor)) {
            $tmLead->next_followup_date = $request->next_followup_date;
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

        $tmLeadsIds = array_map('intval', explode(',', $tmLeadsIds));
        foreach($tmLeadsIds as $tmLeadsId) {
            $updateTmLead = TmLead::find($tmLeadsId);
            $updateTmLead->assigned_to_id = $assignedToUserIdNew;
            $updateTmLead->save();
        }
        return $assignedToUserIdNew;
    }

    public function tmLeadsGetPrioritizeLead($currentUserID) //
    {
        $prioritizeLeads = TmLead::select('tm_leads.id as tmLeadId')
        ->leftjoin('tm_lead_statuses','tm_leads.tm_lead_statuses_id','tm_lead_statuses.id')
        ->whereNotIn('tm_lead_statuses.code', ['NotContactablePE','CarSold','NotEligible','NotInterested'
        ,'PurchasedBeforeFirstCall','PurchasedFromCompetitor','WrongNumber','DONOTCALL','Duplicate'
        ,'Recycled','Revived','RevivedByNewBusiness','RevivedByRenewals'])
        ->whereRaw('tm_leads.is_deleted=0 AND (tm_leads.next_followup_date IS NULL OR tm_leads.next_followup_date < now()) AND tm_leads.assigned_to_id='.$currentUserID)
        ->orderByRaw('tm_leads.next_followup_date IS NULL, tm_leads.next_followup_date, tm_leads.created_at')->limit(1)->get();

        if(!empty($prioritizeLeads)) {
            foreach($prioritizeLeads as $prioritizeLead)
            {
                $prioritizeLeadId = $prioritizeLead->tmLeadId;
            }
        }
        else {
            $prioritizeLeadId = "";
        }

        return $prioritizeLeadId;
    }
}
