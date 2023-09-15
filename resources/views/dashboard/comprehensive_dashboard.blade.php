@extends('layouts.app_livewire')

@php
use App\Enums\PermissionsEnum;
@endphp

@section('title','Comprehensive Dashboard')
@section('content')

@push('scripts')
<meta name="csrf-token" content="{{ csrf_token() }}" />
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>
<script src="https://unpkg.com/slim-select@latest/dist/slimselect.min.js"></script>
<link href="https://unpkg.com/slim-select@latest/dist/slimselect.css" rel="stylesheet"></link>
<style>
    .ss-main .ss-values .ss-value .ss-value-delete {
        width: 18px;
    }
</style>
<script>
    var comprehensiveDashboardStatChart = {};
    var comprehensiveDashboardStats = <?php echo json_encode($comprehensiveDashboardStats) ?>;

    function createComprehensiveConversionChart(comprehensiveDashboardStats) {
        var data = [];
        for (let index = 0; index < comprehensiveDashboardStats[0].length; index++) {
            data.push({
                name: comprehensiveDashboardStats[0][index],
                y: Number(comprehensiveDashboardStats[1][index])
            })
        }
        comprehensiveDashboardStatChart = Highcharts.chart('comprehensiveConversion', {
            chart: {
                type: 'column'
            },
            title: {
                align: 'center',
                text: 'COMPREHENSIVE CONVERSION REPORT',
                fontSize: '40'
            },
            xAxis: {
                type: 'category'
            },
            yAxis: {
                title: {
                    text: 'Total Net Conversion'
                }

            },
            legend: {
                enabled: false
            },
            plotOptions: {
                series: {
                    borderWidth: 0,
                    dataLabels: {
                        enabled: true,
                        format: '{point.y:.2f}%'
                    }
                }
            },

            tooltip: {
                headerFormat: '<span style="font-size:11px">{series.name}</span><br>',
                pointFormat: '<span style="color:{point.color}">{point.name}</span>: <b>{point.y:.2f}%</b>'
            },

            series: [{
                name: 'Net Conversion',
                colorByPoint: true,
                data: data
            }],
            credits: {
                enabled: false,
            },
        });

    }
    const agentFilter = new SlimSelect({
        select: '#user-filter',
        settings: {
            allowDeselect: true,
            placeholderText: 'Select Users',
            disabled: true,
        }
    })
    const tierFilter = new SlimSelect({
        select: '#tier-filter',
        settings: {
            allowDeselect: true,
            placeholderText: 'Select Tiers',
        }
    })
    const subTeamFilter = new SlimSelect({
        select: '#sub-team-filter',
        settings: {
            allowDeselect: true,
            placeholderText: 'Select Sub Teams',
            disabled: true,
        },
        events: {
            beforeChange: (newVal, oldVal) => {
                if (newVal.length === 0) {
                    agentFilter.setData([]);
                    agentFilter.disable();
                }
                return true;
            }
        }
    })
    const teamFilter = new SlimSelect({
        select: '#team-filter',
        settings: {
            allowDeselect: true,
            placeholderText: 'Select Teams',
        },
        events: {
            beforeChange: (newVal, oldVal) => {
                if (newVal.length === 0) {
                    subTeamFilter.setData([]);
                    subTeamFilter.disable();
                    agentFilter.setData([]);
                    agentFilter.disable();
                }
                return true;
            }
        }
    })
    $(function() {
        createComprehensiveConversionChart(comprehensiveDashboardStats);
        $('#tier-filter, #user-filter, #team-filter, #sub-team-filter, #excludeManualFilter, #commercial-filter').on('change', function(e) {
            var tierFilterValue = $('#tier-filter').val();
            var userFilterValue = $('#user-filter').val();
            var teamFilterValue = $('#team-filter').val();
            var subTeamFilterValue = $('#sub-team-filter').val();

            var commercialFilterValue = $('#commercial-filter').val();
            if (e.target.id == 'team-filter') {
                if (!teamFilterValue) return;
                fetchSubTeams(teamFilterValue);
            } else if (e.target.id == 'sub-team-filter') {
                if (!subTeamFilterValue) return;
                $.ajax({
                    url: "/get-users-by-sub-team",
                    type: "post",
                    data: {
                        'sub_team_filter': subTeamFilterValue
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(users) {
                        if (users) {
                            users = users.map(user => {
                                return { text: user.name, value: user.id }
                            })
                            agentFilter.setData(users);
                            if (users.length > 0) {
                                agentFilter.enable();
                            } else {
                                agentFilter.disable();
                            }
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.log(textStatus, errorThrown);
                    }
                });
            }

            var excludeFilterValue = $('#excludeManualFilter option:selected').val();
            comprehensiveDashboardStatChart.showLoading();
            $.ajax({
                url: "/get-comp-filter-stats",
                type: "post",
                data: {
                    'tier_filter': tierFilterValue,
                    'team_filter': teamFilterValue,
                    'sub_team_filter': subTeamFilterValue,
                    'userFilter': userFilterValue,
                    'excludeFilter': excludeFilterValue,
                    'isCommercial': commercialFilterValue
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(result) {
                    if (result) {
                        var labels = (typeof result[0]) == 'string' ? JSON.parse(result[0]) : result[0];
                        var data = (typeof result[1]) == 'string' ? JSON.parse(result[1]) : result[1];
                        var numbers = [];
                        for (let index = 0; index < data.length; index++) {
                            numbers.push(Number(data[index]));
                        }
                        if (labels.length > 0) {
                            comprehensiveDashboardStatChart.destroy();
                            createComprehensiveConversionChart([labels, numbers]);
                        } else {
                            comprehensiveDashboardStatChart.destroy();
                            createComprehensiveConversionChart([
                                [''],
                                [0]
                            ]);
                        }
                    }
                    comprehensiveDashboardStatChart.hideLoading();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    comprehensiveDashboardStatChart.hideLoading();
                    console.log(textStatus, errorThrown);
                }
            });
        });

        checkSelectedTeam();
    });

    function checkSelectedTeam () {
        const selectedTeam = $('#team-filter').val();
        if (selectedTeam && selectedTeam.length > 0) {
            fetchSubTeams(selectedTeam);
        }
    }

    function fetchSubTeams(teams) {
        $.ajax({
            url: "/get-sub-teams-by-team",
            type: "post",
            data: {
                'team_filter': teams
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(users) {
                if (users) {
                    users = users.map(user => {
                        return { text: user.name, value: user.id }
                    })
                    subTeamFilter.setData(users);
                    if (users.length > 0) {
                        subTeamFilter.enable();
                    } else {
                        subTeamFilter.disable();
                        agentFilter.setData([]);
                        agentFilter.disable();
                    }
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log(textStatus, errorThrown);
            }
        });
    }
    new SlimSelect({
        select: '#commercial-filter',
        settings: {
            allowDeselect: false,
            placeholderText: 'Commercial filter',
        }
    })
</script>
@endpush

<div>
    <div class="flex gap-4 justify-end mb-4">
        @can(PermissionsEnum::ViewTeamsFilters)
            <div class="md:w-1/4">
                <label>Teams</label>
                <select multiple name="teams[]" id="team-filter">
                    <option data-placeholder="true"></option>
                    @foreach ($teams as $team)
                    <option @if($team->name == 'Organic') selected="selected" @endif value="{{$team->id}}">{{$team->name}}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:w-1/4">
                <label>Sub Teams</label>
                <select multiple name="sub_teams[]" id="sub-team-filter">
                    <option data-placeholder="true"></option>
                </select>
            </div>
        @endcan
        <div class="md:w-1/4">
            <label>Commercial</label>
            <select name="isCommercial" id="commercial-filter" placeholder="Select any option">
                <option data-placeholder="true"></option>
                <option value="All" selected>All</option>
                <option value="true">Yes</option>
                <option value="false">No</option>
            </select>
        </div>
        <div class="md:w-1/4">
            <label>Advisor</label>
            <select multiple name="users[]" id="user-filter">
                <option data-placeholder="true"></option>

            </select>
        </div>
        <div class="md:w-1/4">
            <label>Tiers</label>
            <select multiple name="tiers[]" id="tier-filter">
                <option data-placeholder="true"></option>
                @foreach ($tiers as $tier)
                <option value="{{$tier->id}}"> {{ $tier->name }} </option>
                @endforeach
            </select>
        </div>

    </div>
    <div>
        <div id="comprehensiveConversion"></div>
    </div>
</div>
@endsection
