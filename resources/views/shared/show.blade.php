@extends('layouts.app')
@section('title', $model->modelType . ' Detail')
@section('content')
    <style>
        #quote-plans table.dataTable thead .sorting_asc:after {
            content: none !important;
        }

        .select2-results__option--selected {
            display: none;
        }

        .select2-results__option[aria-selected=true] {
            display: none;
        }

        .modal-dialog,
        .modal-content {
            /* 80% of window height */
            height: 200px !important;
        }

        .modal-body {
            max-height: 140px !important;
        }

    </style>
    <?php
    use App\Enums\quoteTypeCode;
    ?>
    <div class="row">
        <div class="col-md-12 col-sm-12 admin-detail">
            <div class="x_panel">
                <br />
                @if (session()->has('success'))
                    <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                @if (session()->has('message'))
                    <div class="alert alert-danger">{{ session()->get('message') }}</div>
                @endif
                <div class="alert alert-success" style="display: none" id="teamassignmentSuccess"></div>
                <div class="x_title">
                    <h2>{{ (str_contains(strtolower($model->modelType), 'team')? 'Team': (str_contains(strtolower($model->modelType), 'leadstatus')? 'Lead Status': $model->modelType)) . ' Detail' }}
                    </h2>
                    <ul class="nav navbar-right panel_toolbox">
                        @if(count($allowedDuplicateLOB) > 0)
                        <li> <a id="duplicateLeadModalBtn" class="btn btn-warning btn-sm">Duplicate Lead</a> </li>
                        @endif
                        <li><a href="{{ url('quotes/' . strtolower($model->modelType)) }}"
                                class="btn btn-warning btn-sm">{{ (str_contains(strtolower($model->modelType), 'team')? 'Team': (str_contains(strtolower($model->modelType), 'leadstatus')? 'Lead Status': $model->modelType)) . ' List' }}</a>
                        </li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                @hasanyrole('ADMIN|HEALTH_MANAGER|WCU_ADVISOR|HEALTH_DEPUTY')
                    @if (strtolower($model->modelType) == 'health' && $record->health_team_type != '')
                        <form method="post" action="manualLeadAssignAfterTeamAssign" class="form-horizontal form-label-left"
                            autocomplete="off">
                            {{ csrf_field() }}
                            @method('POST')
                            <input type="hidden" value="{{ strtolower($model->modelType) }}" name="modelType">
                            <input type="hidden" value="{{ strtolower($record->id) }}" name="entityId">
                            <div class="col-md-6">
                                <div class="col-md-4">
                                    <h2><b>Assign Lead</b></h2>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-control" id="assigned_to_id_new" name="assigned_to_id_new">
                                        <option>Select Assignee</option>
                                        @foreach ($advisors as $item)
                                            <option @if ($record->advisor_id == $item->id) selected="selected" @endif
                                                value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                    <label id='userAssignValidation' style="display: none;color:red;">Please user for
                                        assignment</label>
                                </div>
                                <div class="col-md-4">
                                    <button id="assignAfterTeam" name="assignAfterTeam"
                                        class="btn btn-warning btn-sm">Assign</button>
                                </div>
                            </div>
                            <div class="clearfix">
                            </div>
                        </form>
                    @elseif (strtolower($model->modelType) == 'health' && ($record->health_team_type == '' || $record->health_team_type == null))
                        <form method="post" id="healthTeamAssignForm" action="healthTeamAssign"
                            class="form-horizontal form-label-left" autocomplete="off">
                            {{ csrf_field() }}
                            <input type="hidden" value="{{ strtolower($model->modelType) }}" name="modelType">
                            <input type="hidden" value="{{ $record->id }}" id="entityId" name="entityId">
                            <div class="col-md-6">
                                <div class="col-md-4">
                                    <h2><b>Assign Lead Team</b></h2>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-control" id="assign_team" name="assign_team">
                                        <option value="">Select Team</option>
                                        <option value="EBP">EBP</option>
                                        <option value="RM-NB">RM-NB</option>
                                        <option value="RM-Speed">RM-Speed</option>
                                        <option value="GM">Group Medical</option>
                                    </select>
                                    <label id='teamAssignValidation' style="display: none;color:red;">Please select a team for
                                        assignment</label>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" id="assignTeamBtn" name="assignTeamBtn"
                                        class="btn btn-warning btn-sm">Assign Team</button>
                                </div>
                            </div>
                            <div class="clearfix">
                            </div>
                        </form>
                    @endif
                @endcan
                @if (strtolower($model->modelType) == 'business' && Auth::user()->hasAnyRole(['ADMIN', 'BUSINESS_MANAGER', 'WCU_ADVISOR', 'BUSINESS_DEPUTY']) && ($record->business_type_of_insurance_id_text = 'Group Medical'))
                    <form method="post" action="manualBusinessLeadAssign" class="form-horizontal form-label-left"
                        autocomplete="off">
                        {{ csrf_field() }}
                        @method('POST')
                        <input type="hidden" value="{{ strtolower($model->modelType) }}" name="modelType">
                        <input type="hidden" value="{{ strtolower($record->id) }}" name="entityId">
                        <div class="col-md-6">
                            <div class="col-md-4">
                                <h2><b>Assign Lead</b></h2>
                            </div>
                            <div class="col-md-4">
                                <select class="form-control" id="assigned_to_id_new" name="assigned_to_id_new">
                                    <option>Select Assignee</option>
                                    @foreach ($advisors as $item)
                                        <option @if ($record->advisor_id == $item->id) selected="selected" @endif
                                            value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                                <label id='userAssignValidation' style="display: none;color:red;">Please user for
                                    assignment</label>
                            </div>
                            <div class="col-md-4">
                                <button id="assignAfterTeam" name="assignAfterTeam"
                                    class="btn btn-warning btn-sm">Assign</button>
                            </div>
                        </div>
                        <div class="clearfix">
                        </div>
                    </form>
                @endif
                <div class="x_content">

                    @php
                        $count = 1;
                    @endphp
                    @foreach ($model->properties as $property => $value)
                        @if (!str_contains($model->skipProperties['show'], $property))
                            @if ($count % 2 != 0)
                                <div class="item form-group">
                            @endif
                            <div class="col">
                                @if (strpos($value, 'title'))
                                    <label class="col-form-label col-md-6 col-sm-6"
                                        for="Status Description"><b>{{ strtoupper($customTitles[$property]) }}</b></label>
                                @else
                                    <label class="col-form-label col-md-6 col-sm-6"
                                        for="Status Description"><b>{{ str_replace('_', ' ', strtoupper($property)) }}</b></label>
                                @endif
                                @if (str_contains($value, 'select'))
                                    @if (str_contains($value, 'customTable'))
                                        <div class="col-md-6 col-sm-6"
                                            style="text-overflow: ellipsis;overflow: auto;white-space: nowrap;width: 495px;">
                                            <p class="label-align-center">{{ $customTableList[$property][0]->names }}</p>
                                        </div>
                                    @else
                                        <div class="col-md-6 col-sm-6">
                                            @php
                                                $propertyName = $property . '_text';
                                            @endphp
                                            <p class="label-align-center">{{ $record->$propertyName }}</p>
                                        </div>
                                    @endif
                                @else
                                    @if (str_contains($value, 'customTable'))
                                        <div class="col-md-6 col-sm-6"
                                            style="text-overflow: ellipsis;overflow: auto;white-space: nowrap;width: 495px;">
                                            <p class="label-align-center">{{ $customTableList[$property][0]->names }}</p>
                                        </div>
                                    @else
                                        <div class="col-md-6 col-sm-6"
                                            style="text-overflow: ellipsis;overflow: auto;white-space: nowrap;width: 495px;">
                                            <p class="label-align-center">{{ $record->$property }}</p>
                                        </div>
                                    @endif
                                @endif
                            </div>
                            @if (count($model->properties) == $count && $count % 2 != 0)
                                <div class="col"></div>
                </div>
            @elseif($count % 2 == 0)
            </div>
            @endif
            @php
                $count++;
            @endphp
            @endif
            @endforeach
            <div class="ln_solid"></div>
            <div class="row">
                <div class="col-auto mr-auto"></div>
                <div class="col-auto">
                    @if (strtolower($model->modelType) == 'business')
                        @can('corpline-quotes-edit')
                            <a id="texta" href="{{ url('quotes/' . strtolower($model->modelType) . '/' . $record->uuid . '/edit') }}"
                                class='btn btn-warning btn-sm'>Edit</a>
                        @endcan
                    @endif
                    @can(strtolower($model->modelType) . '-quotes-edit')
                        <a id="texta" href="{{ url('quotes/' . strtolower($model->modelType) . '/' . $record->uuid . '/edit') }}"
                            class='btn btn-warning btn-sm'>Edit</a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>
    @if (strtolower($model->modelType) == "home")
        </div>
    @endif
    @if (strtolower($model->modelType) != 'teams' && strtolower($model->modelType) != 'leadstatus')
        <x-lead-status-update :lead="$record" :modeltype="$model->modelType" :status="$record->quote_status_id"
            :statuses="$leadStatuses" :lostreasons="$lostReasons" :selectedlostreason="$selectedLostReasonId" />
    @endif
    @if(count($allowedDuplicateLOB) > 0)
    <div class="modal fade" id="duplicateLeadModal" name="duplicateLeadModal" tabindex="-1" role="dialog"
        aria-labelledby="duplicateLeadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
           
                <div class="modal-content" style="display: grid;    height: 260px !important;">
                    <form method="post" action="/quotes/createDuplicate" autocomplete="off">
                        {{ csrf_field() }}
                        @method('POST')
                        <input type="hidden" value="{{ strtolower($model->modelType) }}" name="modelType">
                        <input type="hidden" value="{{ strtolower($model->modelType) }}" name="parentType">
                        <input type="hidden" value="{{ strtolower($record->id) }}" name="entityId">
                        <input type="hidden" value="{{ strtolower($record->code) }}" name="entityCode">
                        <input type="hidden" value="{{ strtolower($record->uuid) }}" name="entityUId">
                    <div class="modal-header">
                        <h5 class="modal-title" id="duplicateLeadModalLabel" style="font-size: 16px !important;"><span
                                class="fa fa-clone"></span>
                            <strong style="margin-left: 13px;">Duplicate Lead</strong>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="height: 138px;">
                        <select class="form-control select2" multiple="multiple" id="lob_team" name="lob_team[]">
                            @foreach ($allowedDuplicateLOB as $item)
                                <option value="{{ $item }}">{{ $item }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer" style="justify-content: center; padding : 0px !important;">
                        <button type="submit" style="margin-top: 13px;" class="btn btn-sm btn-success">Create Duplicate</button>
                    </div>
                </form>
                </div>
           
        </div>
    </div>
    @endif
    @if ($model->modelType == quoteTypeCode::Car)
        <div class="modal fade" id="quotePlanModal" name="quotePlanModal" tabindex="-1" role="dialog"
            aria-labelledby="quotePlanModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content" style="height: 40vw;">
                    <div class="modal-header" style="border-bottom: none;">
                        <h5 class="modal-title" id="quotePlanModalLabel"><svg xmlns="http://www.w3.org/2000/svg"
                                width="16" height="16" fill="currentColor" class="bi bi-grid-3x3-gap-fill"
                                viewBox="0 0 16 16" style="vertical-align: unset;">
                                <path
                                    d="M1 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V2zM1 7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V7zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V7zM1 12a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-2z" />
                            </svg> <strong>Plan Details</strong></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="quote-plan-modal-body"> </div>
                    <div class="modal-footer" style="border: none">

                    </div>
                </div>
            </div>
        </div>



        <x-car-quote-more-detail :vehicleTypeText="$vehicleTypeText" :caqQuoteCylinder="$record->cylinder"
            :caqQuoteSeatCapacity="$record->seat_capacity" :listQuote="$listQuote" />

        <x-car-ecom-detail :carQuotePremium="$record->premium" :carQuotePaidAt="$record->paid_at"
            :carQuotePaymentStatus="$record->payment_status_id_text" :carQuotePlanName="$record->plan_id_text"
            :carQuotePlanAddons="$carQuotePlanAddons" :carQuotePlanProvider="$record->car_plan_provider_id_text"
            :carQuotePaymentMethod="$record->payment_gateway" />

 

            <div class="row" st>
                <div class="col-md-12 col-sm-12">
                    <div class="x_panel">
                        <div class="x_title">
                            <h2>Lead History</h2>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                            <div id="lead-history-div">
                                <table id="leadhistorydatatable" class="table table-striped jambo_table" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Modified At</th>
                                            <th>Modified By</th>
                                            <th>Lead Status</th>
                                            <th>Advisor</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="5" style="text-align: center"> <button id="loadHistoryDataBtn" class="btn btn-success btn-sm">Load History Data</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        

        <x-car-quote-plans :listQuotePlans="$listQuotePlans" :uuid="$ecomCarInsuranceQuoteUrl.$record->uuid"
            :uuidModal="$record->uuid" :quoteRequestId="$record->id" :quoteIsCommerce="$record->is_ecommerce" />
    @endif

    @if ($model->modelType == quoteTypeCode::Travel)
        <div class="modal fade" id="quotePlanModal" name="quotePlanModal" tabindex="-1" role="dialog"
            aria-labelledby="quotePlanModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content" style="height: 40vw;">
                    <div class="modal-header" style="border-bottom: none;">
                        <h5 class="modal-title" id="quotePlanModalLabel"><svg xmlns="http://www.w3.org/2000/svg"
                                width="16" height="16" fill="currentColor" class="bi bi-grid-3x3-gap-fill"
                                viewBox="0 0 16 16" style="vertical-align: unset;">
                                <path
                                    d="M1 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V2zM1 7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V7zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V7zM1 12a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-2zm5 0a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-2z" />
                            </svg> <strong>Plan Details</strong></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="quote-plan-modal-body"> </div>
                    <div class="modal-footer" style="border: none">

                    </div>
                </div>
            </div>
        </div>
        <x-travel-ecom-detail
            :travelQuotePremium="$record->premium"
            :travelQuotePaidAt="$record->paid_at"
            :travelQuotePaymentStatus="$record->payment_status_id_text"
            :travelQuotePlanName="$record->plan_id_text"
        />
        <x-travel-quote-members-detail
            :members="$members_detail"
        />
        <x-travel-quote
            :listQuotePlans="$listQuotePlans"
            :uuidModal="$record->uuid"
            :quoteRequestId="$record->id" />
    @endif

    

    @can('auditable')
        <div id="auditable">
            <button id='auditablebtn' class="btn btn-warning btn-sm auditablebtn" data-id="{{ $record->id }}"
                data-model="App\Models\{{ $model_name }}">
                View Audit Logs
            </button>
        </div>
    @endcan
@endsection
