@extends('layouts.app')
@section('title','Add User')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}" />
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<style>
    .select2-results__option[aria-selected=true] {
        display: none;
    }

    div.dataTables_wrapper div.dataTables_processing {
        font-size: 30px !important;
        border: none !important;
        background-color: transparent !important;
        color: #4183BD !important;
        padding: 0px !important;
        height: 110px !important;
        width: 250px !important;
    }

    .dataTables_paginate .paginate_button.active {
        background: blue !important;
    }

    .pagination {
        margin-top: 12px !important;
    }

    .dataTables_paginate .paginate_button.active a {
        background: #71A1CC !important;
        border-radius: 3px;
        color: white;
    }
</style>
<script>
    function loadAdditionalTeams(additionalTeams, previous_selected_additional_teams) {
        console.log(previous_selected_additional_teams);
        additionalTeams.forEach(element => {
            var select = $("#additionalTeams-select");
            var parentTeamId = $("#user-team-select").val();
            if (element.id != parentTeamId) {
                select.append('<option value="' + element.id + '">' + element.name + '</option>');
            }
        });
        $('#additionalTeams-select').val(previous_selected_additional_teams);
    }

    function loadSubTeams(team_id, previous_sub_team_id) {
        $(".loader").show();
        if (typeof team_id == 'string') {
            team_id = [team_id];
        }
        $.ajax({
            url: '/getSubTeams',
            type: "post",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                'teamId': team_id
            },
            success: function(response) {
                $('#sub-team').find('option').remove().end().append('<option value="0" selected="selected">None</option>');
                for (let index = 0; index < response.length; index++) {
                    const element = response[index];
                    $('#sub-team').append($("<option></option>").attr("value", element.id).text(element.name));
                }
                if (previous_sub_team_id != 0) {
                    $('#sub-team').val(previous_sub_team_id);
                }
                $(".loader").hide();
            },
        });
    }

    function loadManagerByTeam(team_id) {
        $(".loader").show();
        if (typeof team_id == 'string') {
            team_id = [team_id];
        }
        $.ajax({
            url: '/getTeamManagers',
            type: "post",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                'teamId': team_id
            },
            success: function(response) {
                $('#user-manager-select').find('option').remove().end();
                for (let index = 0; index < response.length; index++) {
                    const element = response[index];
                    $('#user-manager-select').append($("<option></option>").attr("value", element.id).text(element.name));
                }
                $('#user-manager-select').removeAttr('disabled');
                $("#user-manager-select").val(window.previous_manager);
                $(".loader").hide();
            },
        });
    }

    function loadTeamsByProduct(productIds) {
        if (typeof productIds == 'string') {
            productIds = [productIds];
        }
        $(".loader").show();
        $.ajax({
            url: '/get-product-teams',
            type: "post",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                'productIds': productIds
            },
            success: function(response) {
                $('#user-team-select').find('option').remove().end();

                for (let index = 0; index < response.length; index++) {
                    const element = response[index];
                    $('#user-team-select').append($("<option></option>").attr("value", element.id).text(element.name));
                }
                $('#user-team-select').val(window.previous_team);
                $(".loader").hide();
            },
        });
    }

    $(document).ready(function() {
        window.previous_sub_team_id = JSON.parse('<?php echo json_encode(old('sub_team_id')); ?>');
        window.previous_team = @json(old('teams'));
        window.previous_product = @json(old('products'));
        window.previous_manager = @json(old('manager'));
        var previous_selected_additional_teams = JSON.parse('<?php echo json_encode(old('additionalTeams')); ?>');
        var previous_selected_roles = JSON.parse('<?php echo json_encode(old('roles')); ?>');
        var additionalTeams = JSON.parse('<?php echo json_encode($products); ?>');
        $("#user-product-select").on('change', function() {
            if (this.value == '') {
                $("#user-team-select").empty();
            } else {
                loadTeamsByProduct($(this).val());
                loadManagerByTeam($(this).val());
            }
        });
        if(previous_product) {
            loadTeamsByProduct(previous_product);
        }

        $('#roles').val(previous_selected_roles);
        loadManagerByTeam($("#user-product-select").val());
        loadSubTeams($("#user-team-select").val(), previous_sub_team_id);

        $('#additionalTeams-select').select2({
            placeholder: 'Select teams for MyLeads Tab visiblity',
            width: '100%',
            allowClear: true
        });

        loadAdditionalTeams(additionalTeams, previous_selected_additional_teams);

        $("#user-team-select").on('change', function() {
            if ($(this).val()) {
                $("#additionalTeams-select").empty();
                loadSubTeams($(this).val());
                loadAdditionalTeams(additionalTeams);
            }

        });
    });
</script>
<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Create User</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="{{ route('users.index') }}" class="btn btn-warning btn-sm">Users List</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                @if(session()->has('success'))
                <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
                <form id="demo-form2" method='post' action="{{ route('users.store') }}" enctype="multipart/form-data" data-parsley-validate class="form-horizontal form-label-left" autocomplete="off">
                    {{csrf_field()}}
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="name">Name <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control">
                            @if ($errors->has('name'))
                                <span class="text-danger">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="email">Email Address<span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control">
                            @if ($errors->has('email'))
                                <span class="text-danger">{{ $errors->first('email') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="mobile_no">Mobile Number</label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="mobile_no" name="mobile_no" value="{{ old('mobile_no') }}" class="form-control" pattern="^(?:\+971|00971|0)(?!2)((?:2|3|4|5|6|7|9|50|51|52|55|56)[0-9]{7,})$">
                            @if ($errors->has('mobile_no'))
                                <span class="text-danger">{{ $errors->first('mobile_no') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="landline_no">Landline Number </label>
                        <div class="col-md-6 col-sm-6">
                            <input type="text" id="landline_no" name="landline_no" value="{{ old('landline_no') }}" class="form-control" pattern="^(?:\+971|00971|0)((?:2|3|4|5|6|7|9|50|51|52|55|56)[0-9]{7,})$">
                            @if ($errors->has('landline_no'))
                                <span class="text-danger">{{ $errors->first('landline_no') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="password">Password <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <input type="password" id="password" name="password" value="{{ old('password') }}" class="form-control">
                            @if ($errors->has('password'))
                                <span class="text-danger">{{ $errors->first('password') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="roles">Role <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <select id="roles" name="roles[]" multiple="multiple" style="margin-bottom:15px;" class="form-control select2 select-roles">
                                @foreach(array_chunk($roles, 6) as $chunk)
                                    @foreach($chunk as $skey=>$item)
                                        <option value="{{$item }}" }}>
                                            {{ $item }}
                                        </option>
                                    @endforeach
                                @endforeach
                            </select>
                            @if ($errors->has('roles'))
                                <span class="text-danger">{{ $errors->first('roles') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="roles">Product <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <select name="products[]" id="user-product-select" class="form-control select2" multiple="multiple">>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" {{ (collect(old('products'))->contains($product->id)) ? 'selected':'' }}>{{ $product->name }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('product'))
                                <span class="text-danger">{{ $errors->first('product') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="roles">Teams <span class="required">*</span></label>
                        <div class="col-md-6 col-sm-6">
                            <select name="teams[]" id="user-team-select" class="form-control select2" multiple="multiple">>
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}" {{ (collect(old('teams'))->contains($team->id)) ? 'selected':'' }}>{{ $team->name }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('team'))
                                <span class="text-danger">{{ $errors->first('team') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="roles">Sub-Team</label>
                        <div class="col-md-6 col-sm-6">
                            <select name="sub_team_id" id="sub-team" class="form-control">
                                @foreach ($subTeams as $team)
                                    @if (old('sub_team_id') == $team->id)
                                        <option value="{{ $team->id }}" selected>{{ $team->name }}</option>
                                    @else
                                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @if ($errors->has('team'))
                                <span class="text-danger">{{ $errors->first('team') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="roles">LOB Visibility</label>
                        <div class="col-md-6 col-sm-6">
                            <select name="additionalTeams[]" id="additionalTeams-select" class="form-control select2" multiple="multiple">
                            </select>
                        </div>
                        <div class="col-md-3 col-sm-3">
                            <i class="fa fa-info-circle" id="tooltipGm" style="margin-top: 13px;" title="Additional teams selection helps advisor see leads from selected teams as well"></i>
                        </div>
                    </div>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 label-align" for="roles">Manager</label>
                        <div class="col-md-6 col-sm-6">
                            <select disabled="disabled" id='user-manager-select' name="manager[]" class="form-control select2" multiple="multiple">
                            </select>
                            @if ($errors->has('manager'))
                                <span class="text-danger">{{ $errors->first('manager') }}</span>
                            @endif
                        </div>
                    </div>
                    <div id='redirect_to_view_div'></div>
                    <div class="ln_solid"></div>
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-warning btn-sm" id="return_to_view">Create</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
