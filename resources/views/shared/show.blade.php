@extends('layouts.app')
@section('title', $model->modelType . ' Detail')
@section('content')
    @inject('tierService', 'App\Services\TierService')
    @php
        use App\Enums\quoteTypeCode;
        use App\Models\CarQuote;
        use App\Enums\RolesEnum;
        use App\Enums\QuoteStatusEnum;
        use App\Enums\PermissionsEnum;
        use App\Enums\DatabaseColumnsString;
        use App\Enums\GenericRequestEnum;
        use App\Enums\LeadSourceEnum;
    @endphp

    <script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
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

        .modal-tall .modal-body {
            position: relative;
            min-height: 600px;
            padding: 15px;
        }

        .custom-checkbox {
            cursor: pointer;
            display: block;
            font-size: 16px;
            line-height: 26px;
            margin: 0 0 20px;
            padding: 0 0 0 40px;
            position: relative;
        }

        .custom-checkbox input[type="checkbox"] {
            display: none;
        }

        .custom-checkbox span.checkbox {
            background-color: #fff;
            border: solid 2px #cccccc;
            border-radius: 50%;
            cursor: pointer;
            display: block;
            height: 26px;
            margin: 0px;
            position: absolute;
            left: 0;
            top: 0px;
            width: 26px;
        }

        .custom-checkbox input[type='checkbox']:checked+span.checkbox {
            background: #26B99A;
            border-color: #169F85;
            text-align: center;
        }

        .custom-checkbox input[type='checkbox']:checked+span.checkbox:before {
            content: "\f00c";
            color: #fff;
            font: normal normal normal 20px/1 FontAwesome;
        }

        .col {
            padding-left: 8px;
        }

        .ebp_dob {
            z-index: 99999 !important;
        }
    </style>
    <script>
        $('#add-activity-btn').on('click', function() {
            $('#activityModal').modal({
                show: true
            });
        });
        $(function() {
            $('#manualTierAssignmentBtn').on('click', function(e) {
                debugger;
                e.preventDefault();
                if ($('#new_selected_tier').val() == '') {
                    $('#tierAssignValidation').show().fadeOut(5000);
                } else {
                    $.ajax({
                        url: '/quotes/manual-tier-assignment',
                        type: 'POST',
                        data: {
                            selectedLeadId: $('#entityId').val(),
                            selectedTierId: $('#new_selected_tier').val(),
                            modelType: $('#modelType').val(),
                            entityCode: $('#entityCode').val(),
                            _token: config._token,
                        },
                        success: function(response) {
                            $('#teamassignmentSuccess')
                                .html('Tier Assigned Successfully')
                                .show()
                                .fadeOut(5000);
                            setTimeout(() => {
                                window.location.reload(true);
                            }, 2000);
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.log(jqXHR, textStatus, errorThrown);
                        },
                    });
                }
            });
        });
    </script>
    @php
        $updatedTitles = [
            'premium' => 'price',
        ];
    @endphp
    <div class="row">
        <div class="col-md-12 col-sm-12 admin-detail">
            @if ($model->modelType == quoteTypeCode::Car)
                <x-car-ecom-detail :record="$record" :mainPayment="$mainPayment" :payments="$payments" :carQuotePlanAddons="$carQuotePlanAddons" />
            @endif
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
                    <h2>{{ (str_contains(strtolower($model->modelType), 'team') ? 'Team' : (str_contains(strtolower($model->modelType), 'leadstatus') ? 'Lead Status' : $model->modelType)) . ' Detail' }}
                    </h2>
                    <ul class="nav navbar-right panel_toolbox">
                        @cannot(PermissionsEnum::ApprovePayments)
                            @if (count($allowedDuplicateLOB) > 0)
                                @if (str_contains(strtolower($model->modelType), 'car'))
                                    @if (!auth()->user()->hasAnyRole([RolesEnum::CarAdvisor, RolesEnum::CarManager]))
                                        <li><a id="duplicateLeadModalBtn" class="btn btn-warning btn-sm">Duplicate Lead</a></li>
                                    @endif
                                @else
                                    <li><a id="duplicateLeadModalBtn" class="btn btn-warning btn-sm">Duplicate Lead</a></li>
                                @endif
                            @endif
                        @endcannot
                        <li><a href="{{ url('quotes/' . strtolower($model->modelType)) }}"
                                class="btn btn-warning btn-sm">{{ (str_contains(strtolower($model->modelType), 'team') ? 'Team' : (str_contains(strtolower($model->modelType), 'leadstatus') ? 'Lead Status' : $model->modelType)) . ' List' }}</a>
                        </li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                @cannot(PermissionsEnum::ApprovePayments)
                    @php
                        $tiersExceptTierR = $tierService->getTiersExceptTierR();
                        if (strtolower($model->modelType) == strtolower(quoteTypeCode::Car)) {
                            $isTierRAssigned = $tierService->isTierRAssigned($record->tier_id);
                        }
                    @endphp
                    @if (strtolower($model->modelType) == strtolower(quoteTypeCode::Car) &&
                            Auth::user()->hasAnyRole(RolesEnum::LeadPool) &&
                            $isTierRAssigned)
                        <form method="post" action="manual-tier-assignment" class="form-horizontal form-label-left"
                            autocomplete="off">
                            {{ csrf_field() }}
                            @method('POST')
                            <input type="hidden" value="{{ strtolower($model->modelType) }}" name="modelType" id="modelType">
                            <input type="hidden" value="{{ strtolower($record->id) }}" name="entityId" id="entityId">
                            <input type="hidden" value="{{ strtolower($record->uuid) }}" name="entityCode" id="entityCode">
                            <div class="col-md-6">
                                <div class="col-md-4">
                                    <h2><b>Assign Tier</b></h2>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-control" id="new_selected_tier" name="new_selected_tier">
                                        <option value="">Select Tier</option>
                                        @foreach ($tiersExceptTierR as $tier)
                                            <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                                        @endforeach
                                    </select>
                                    <label id='tierAssignValidation' style="display: none;color:red;">Please select tier for
                                        assignment.</label>
                                </div>
                                <div class="col-md-4">
                                    <button id="manualTierAssignmentBtn" name="manualTierAssignmentBtn"
                                        class="btn btn-warning btn-sm">Assign</button>
                                </div>
                            </div>
                            <div class="clearfix">
                            </div>
                        </form>
                    @endif


                    @if (Auth::user()->hasAnyRole([RolesEnum::Admin, RolesEnum::HealthManager, RolesEnum::HealthDeputyManager]))
                        @if (strtolower($model->modelType) == strtolower(quoteTypeCode::Health) &&
                                $record->quote_status_id != QuoteStatusEnum::TransactionApproved)
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
                                            <option @if ($record->health_team_type == 'Entry-Level') selected="selected" @endif
                                                value="Entry-Level">Entry-Level
                                            </option>
                                            <option @if ($record->health_team_type == 'Best') selected="selected" @endif
                                                value="Best">Best
                                            </option>
                                            <option @if ($record->health_team_type == 'Good') selected="selected" @endif
                                                value="Good">
                                                Good</option>
                                            <option @if ($record->health_team_type == 'GM') selected="selected" @endif
                                                value="GM">Group
                                                Medical</option>
                                        </select>
                                        <label id='teamAssignValidation' style="display: none;color:red;">Please select a team
                                            for
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
                            @if (Auth::user()->hasAnyRole([RolesEnum::Admin, RolesEnum::HealthManager, RolesEnum::HealthDeputyManager]) &&
                                    $autoAllocationDisabled == '0')
                                )
                                <form method="post" action="manualLeadAssign" class="form-horizontal form-label-left"
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
                        @endif
                    @endif

                    @if (strtolower($model->modelType) == strtolower(quoteTypeCode::Business) &&
                            Auth::user()->hasAnyRole(['ADMIN', 'BUSINESS_MANAGER', 'WCU_ADVISOR', 'BUSINESS_DEPUTY']) &&
                            ($record->business_type_of_insurance_id_text = 'Group Medical'))
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
                @endcannot
                <div class="x_content">
                    @php
                        $count = 1;
                        $searchProperties = [];
                        $skipProperties = [];
                        if ($isRenewalUser && strtolower($model->modelType) == 'car') {
                            $searchProperties = $model->renewalSearchProperties;
                            $skipProperties = $model->renewalSkipProperties;
                        } elseif ($isRenewalUser && strtolower($model->modelType) != 'car') {
                            $searchProperties = $model->renewalSearchProperties;
                            $skipProperties = $model->renewalSkipProperties;
                        } elseif ($isNewBusinessUser && strtolower($model->modelType) != 'car') {
                            $searchProperties = $model->newBusinessSearchProperties;
                            $skipProperties = $model->newBusinessSkipProperties;
                        } else {
                            $searchProperties = $model->searchProperties;
                            $skipProperties = $model->skipProperties;
                        }
                    @endphp
                    @php
                        // MS 14-Jan-2023: For car_quote detail_view set visibility of 'Source' column conditional
                        if (
                            $model->modelType == quoteTypeCode::Car &&
                            auth()
                                ->user()
                                ->hasRole(RolesEnum::CarAdvisor)
                        ) {
                            $skipPropertiesArray = array_filter(
                                explode(',', $skipProperties['show'] . ',source,device'),
                            );
                        } else {
                            $skipPropertiesArray = array_filter(explode(',', $skipProperties['show']));
                        }
                    @endphp
                    @foreach ($model->properties as $property => $value)
                        @if (!in_array($property, $skipPropertiesArray))

                            @if ($count % 2 != 0)
                                <div class="item form-group">
                            @endif
                            <div class="col">
                                @if (strpos($value, 'title'))
                                    @php
                                        $textDecorations = $tooltip = '';
                                        if (
                                            in_array(strtoupper($customTitles[$property]), ['REF-ID', 'PARENT REF-ID'])
                                        ) {
                                            $textDecorations =
                                                'text-decoration: underline; text-decoration-style: dotted;';
                                            $tooltip =
                                                strtoupper($customTitles[$property]) == 'REF-ID'
                                                    ? 'Reference ID'
                                                    : 'Parent Reference-ID';
                                        }
                                    @endphp
                                    <label style="{{ $textDecorations }}" class="col-form-label col-md-6 col-sm-6"
                                        data-toggle="tooltip" data-placement="top" title="{{ $tooltip }}"
                                        for="Status Description"><b>{{ strtoupper(array_key_exists(strtolower($customTitles[$property]), $updatedTitles) ? $updatedTitles[strtolower($customTitles[$property])] : $customTitles[$property]) }}</b></label>
                                @else
                                    <label class="col-form-label col-md-6 col-sm-6"
                                        for="Status Description"><b>{{ str_replace('_', ' ', strtoupper(array_key_exists(strtolower($property), $updatedTitles) ? $updatedTitles[strtolower($property)] : $property)) }}</b></label>
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
                                            <p class="label-align-center">
                                                {{ $record->$propertyName }}
                                            </p>
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
                                            <p class="label-align-center">
                                                @if (str_contains($value, 'checkbox') || (str_contains($value, 'static') && str_contains(strtolower($value), 'yes')))
                                                    {{ isset($record->$property) ? 'Yes' : 'No' }}
                                                @elseif(str_contains($value, 'static') && !str_contains(strtolower($value), 'yes'))
                                                    @if ($record->$property == GenericRequestEnum::MALE_SINGLE_VALUE)
                                                        {{ GenericRequestEnum::MALE_SINGLE }}
                                                    @elseif($record->$property == GenericRequestEnum::FEMALE_SINGLE_VALUE)
                                                        {{ GenericRequestEnum::FEMALE_SINGLE }}
                                                    @elseif($record->$property == GenericRequestEnum::FEMALE_MARRIED_VALUE)
                                                        {{ GenericRequestEnum::FEMALE_MARRIED }}
                                                    @else
                                                        {{ $record->$property }}
                                                    @endif
                                                @else
                                                    @if ($property == DatabaseColumnsString::CAR_VALUE || $property == DatabaseColumnsString::CAR_VALUE_TIER)
                                                        {{ number_format($record->$property, 2) }}
                                                    @else
                                                        {{ $record->$property }}
                                                    @endif
                                                @endif
                                            </p>
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
                    @cannot(PermissionsEnum::ApprovePayments)
                        @if (strtolower($model->modelType) == 'business')
                            @can('corpline-quotes-edit')
                                <a id="texta"
                                    href="{{ url('quotes/' . strtolower($model->modelType) . '/' . $record->uuid . '/edit') }}"
                                    class='btn btn-warning btn-sm'>Edit</a>
                            @endcan
                        @endif
                        @if (
                            ($access['carManagerCanEdit'] || $access['carAdvisorCanEdit']) &&
                                auth()->user()->can(strtolower($model->modelType) . '-quotes-edit'))
                            <a id="texta"
                                href="{{ url('quotes/' . strtolower($model->modelType) . '/' . $record->uuid . '/edit') }}"
                                class='btn btn-warning btn-sm'>Edit</a>
                        @elseif(auth()->user()->hasRole([RolesEnum::Admin]) &&
                                auth()->user()->can(strtolower($model->modelType) . '-quotes-edit'))
                            <a id="texta"
                                href="{{ url('quotes/' . strtolower($model->modelType) . '/' . $record->uuid . '/edit') }}"
                                class='btn btn-warning btn-sm'>Edit</a>
                        @endif
                    @endcannot
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>
    @if (strtolower($model->modelType) == 'home')
        </div>
    @endif

    @if (strtolower($model->modelType) != 'team' && strtolower($model->modelType) != 'leadstatus')
        @if (
            $model->modelType == quoteTypeCode::Car &&
                ($record->source == LeadSourceEnum::RENEWAL_UPLOAD || $record->source == LeadSourceEnum::INSLY))
            <x-quote-renewal-card :record="$record" :modeltype="$model->modelType" />
        @endif
        <x-lead-status-update :lead="$record" :modeltype="$model->modelType" :status="$record->quote_status_id" :statuses="$leadStatuses" :lostreasons="$lostReasons"
            :selectedlostreason="$selectedLostReasonId" :activityassignees="$advisors" :isQuoteDocumentEnabled="$isQuoteDocumentEnabled" :quoteTypeId="$quoteTypeId" :tiers="$tiers"
            :paymentEntityModel="@$paymentEntityModel" :lostRejectReasons="@$lostRejectReasons" :lostApproveReasons="@$lostApproveReasons" :carLostChangeStatus="@$carLostChangeStatus" :allowQuoteLogAction="@$allowQuoteLogAction" />
    @endif
    @if (count($allowedDuplicateLOB) > 0)
        <div class="modal fade" id="duplicateLeadModal" name="duplicateLeadModal" tabindex="-1" role="dialog"
            aria-labelledby="duplicateLeadModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content" style="display: grid;">
                    <form method="post" action="/quotes/createDuplicate" autocomplete="off">
                        {{ csrf_field() }}
                        @method('POST')
                        <input type="hidden" value="{{ strtolower($model->modelType) }}" name="modelType">
                        <input type="hidden" value="{{ strtolower($model->modelType) }}" name="parentType">
                        <input type="hidden" value="{{ strtolower($record->id) }}" name="entityId">
                        <input type="hidden" value="{{ strtolower($record->code) }}" name="entityCode">
                        <input type="hidden" value="{{ strtolower($record->uuid) }}" name="entityUId">
                        <div class="modal-header">
                            <h5 class="modal-title" id="duplicateLeadModalLabel" style="font-size: 16px !important;">
                                <span class="fa fa-clone"></span>
                                <strong style="margin-left: 13px;">Duplicate Lead</strong>
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body" style="">
                            <select class="form-control select2" onchange="toggleSubDropDown(this)" multiple="multiple"
                                id="lob_team" name="lob_team[]">
                                @foreach ($allowedDuplicateLOB as $item)
                                    <option value="{{ $item }}">{{ $item }}</option>
                                @endforeach
                            </select>
                            <select class="form-control" style="display:none;margin-top:10px" id="lob_team_sub_selection"
                                name="lob_team_sub_selection">
                                <option value="" disabled selected>Select your option</option>
                                <option value="new_enquiry">New enquiry</option>
                                <option value="record_only">Record purposes only</option>
                            </select>
                        </div>
                        <div class="modal-footer" style="justify-content: center; padding : 0px !important;">
                            <button type="submit" style="margin-top: 13px;" class="btn btn-sm btn-success"
                                onClick="this.form.submit(); this.disabled=true; this.innerHTML='Duplicating…';">Create
                                Duplicate</button>
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

        <x-payments-table :payments="$payments" :paymentMethods="$paymentMethods" :paymentPlainModel="$paymentEntityModel" :modeltype="$model->modelType" />

        <x-car-quote-assumptions :record="$record" :vehicleTypes="$vehicleTypes" :access="$access" :yearsOfManufacture="$yearsOfManufacture"
            :trimList="$trimList" />

        <x-car-quote-plans :record="$record" :listQuotePlans="$listQuotePlans" :access="$access" :ecomUrl="$ecomCarInsuranceQuoteUrl . $record->uuid" :quoteType="$quoteType"
            :quoteTypeId="$quoteTypeId" :carMakeText="$carMakeText" :carModelText="$carModelText" :advisor="$advisor" :daysAfterCapturedPayment="$daysAfterCapturedPayment" />

        <x-car-quote-ep :transactions="$embeddedProducts" :quoteCode="$record->code" :record="$record" />

        @if (isset($isQuoteDocumentEnabled) && $isQuoteDocumentEnabled)
            <x-quote-policy :record="$record" :quoteType="$quoteType" />
            <x-quote-documents :displaySendPolicyButton="$displaySendPolicyButton" :record="$record" :quoteDocuments="$quoteDocuments" :quoteType="$quoteType" />
        @endif
        <x-email-status :emailStatuses="$emailStatuses" />
        <x-notes-for-customer :record="$record" :notesForCustomers="$notesForCustomers" :quoteTypeId="$quoteTypeId" />
        <x-notes-for-customer-modal :record="$record" :quoteTypeId="$quoteTypeId" />
    @endif

    @if (
        $model->modelType == quoteTypeCode::Life ||
            $model->modelType == quoteTypeCode::Home ||
            $model->modelType == quoteTypeCode::Business)
        @if (auth()->user()->hasRole(RolesEnum::BetaUser))
            <x-payments-table :payments="$payments" :paymentMethods="$paymentMethods" :paymentPlainModel="$paymentEntityModel" :insuranceProviders="$insuranceProviders"
                :modeltype="$model->modelType" />
        @endif

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
        @if (auth()->user()->hasRole(RolesEnum::BetaUser))
            <x-payments-table :payments="$payments" :paymentMethods="$paymentMethods" :paymentPlainModel="$paymentEntityModel" :modeltype="$model->modelType"
                :insuranceProviders="$insuranceProviders" />
        @endif
        <x-travel-ecom-detail :payments="$payments" :mainPayment="$mainPayment" :record="$record" :travelQuotePremium="$record->premium" :travelQuotePaidAt="$record->paid_at"
            :travelQuotePaymentStatus="$record->payment_status_id_text" :travelQuotePlanName="$record->plan_id_text" />
        <x-travel-quote-members-detail :members="$membersDetail" />
        <x-quote-policy :record="$record" :quoteType="$quoteType" />
        @if (isset($isQuoteDocumentEnabled) && $isQuoteDocumentEnabled)
            <x-quote-documents :displaySendPolicyButton="$displaySendPolicyButton" :record="$record" :quoteDocuments="$quoteDocuments" :quoteType="$quoteType" />
        @endif
        <x-email-status :emailStatuses="$emailStatuses" />
        <x-travel-quote :listQuotePlans="$listQuotePlans" :uuidModal="$record->uuid" :quoteRequestId="$record->id" :ecomUrl="$ecomTravelInsuranceQuoteUrl . $record->uuid" />
        <x-travel-quote-members-modal :id="$record->id" />
    @endif

    @if ($model->modelType == quoteTypeCode::Health)
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
        @if (isset($isQuoteDocumentEnabled) && $isQuoteDocumentEnabled)
            <x-quote-policy :record="$record" :quoteType="$quoteType" />
            <x-quote-documents :displaySendPolicyButton="$displaySendPolicyButton" :record="$record" :quoteDocuments="$quoteDocuments" :quoteType="$quoteType" />
        @endif
    @endif

    <x-lead-activities :lead="$record" :modeltype="$model->modelType" :activities="$activities" />
    <x-lead-activity-modal :advisors="$advisors" :modeltype="$model->modelType" :record="$record" />
    <x-customer-additional-contact :record="$record" :quoteType="$quoteType" :customerAdditionalContacts="$customerAdditionalContacts" />
    <x-customer-additional-contact-modal :record="$record" :quoteType="$quoteType" />

    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Lead History</h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <div id="lead-history-div">
                        <table id="quoteStatusLogsTable" class="table table-striped jambo_table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Modified At</th>
                                    <th>Modified By</th>
                                    <th>Lead Status From</th>
                                    <th>Lead Status To</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" style="text-align: center"> <button id="loadQuoteStatusLog"
                                            data-quote-type-id="{{ $quoteTypeId }}" class="btn btn-success btn-sm">Load
                                            History Data</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('auditable')
        <div id="auditable">
            <button id='auditablebtn' class="btn btn-warning btn-sm auditablebtn" data-id="{{ $record->id }}"
                data-model="App\Models\{{ $model_name }}">
                View Audit Logs
            </button>
        </div>
    @endcan
    @if ($model->modelType == quoteTypeCode::Car)
        @can('api-logs-view')
            <div id="apilogsdiv">
                <button id='apilogsbtn' class="btn btn-warning btn-sm apilogsbtn" data-id="{{ $record->id }}"
                    data-model="App\Models\{{ $model_name }}">
                    View API Logs
                </button>
            </div>
        @endcan
    @endif
    <script>
        function toggleSubDropDown(el) {
            var selectedLobs = $("#lob_team").val();
            if (selectedLobs.length > 0) {
                $("#lob_team_sub_selection").css('display', 'block');
            } else {
                $("#lob_team_sub_selection").css('display', 'none');
            }
        }
    </script>
@endsection
