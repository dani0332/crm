@extends('layouts.app_livewire')
@section('title','TPL Dashboard')
@php
use App\Enums\PermissionsEnum;
@endphp
@section('content')
@push('scripts')
<meta name="csrf-token" content="{{ csrf_token() }}" />
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>
<script src="https://unpkg.com/slim-select@latest/dist/slimselect.min.js"></script>
<link href="https://unpkg.com/slim-select@latest/dist/slimselect.css" rel="stylesheet">
</link>
<style>
    .ss-main .ss-values .ss-value .ss-value-delete {
        width: 18px;
    }
</style>
<script>
    var tplDashboardStatsBarChart = {};
    var tplDashboardStats = <?php echo json_encode($tplDashboardStats) ?>;

    function createLeadRcdSummaryByTierPieChart(tplDashboardStats) {
        var data = [];
        for (let index = 0; index < tplDashboardStats[0].length; index++) {
            data.push({
                name: tplDashboardStats[0][index],
                y: Number(tplDashboardStats[1][index])
            })
        }
        tplDashboardStatsBarChart = Highcharts.chart('tplConversionDiv', {
            chart: {
                type: 'column'
            },
            title: {
                align: 'center',
                text: 'TPL CONVERSION REPORT',
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
    $(function() {
        createLeadRcdSummaryByTierPieChart(tplDashboardStats);

        const agentFilter = new SlimSelect({
            select: '#user-filter',
            settings: {
                allowDeselect: true,
                placeholderText: 'Select Users',
                disabled: true,
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
                        agentFilter.setData([]);
                        agentFilter.disable();
                    }
                    return true;
                }
            }
        })

        $('#user-filter, #team-filter, #excludeManualFilter').on('change', function(e) {
            var userFilterValue = $('#user-filter').val();
            var teamFilterValue = $('#team-filter').val();

            if (e.target.id == 'team-filter') {
                if (!teamFilterValue) return;
                fetchTeamUsers(teamFilterValue);
            }

            var excludeFilterValue = $('#excludeManualFilter option:selected').val();
            tplDashboardStatsBarChart.showLoading();
            $.ajax({
                url: "/get-tpl-filter-stats",
                type: "post",
                data: {
                    'team_filter': teamFilterValue,
                    'userFilter': userFilterValue,
                    'excludeFilter': excludeFilterValue
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
                            tplDashboardStatsBarChart.destroy();
                            createLeadRcdSummaryByTierPieChart([labels, numbers]);
                        } else {
                            tplDashboardStatsBarChart.destroy();
                            createLeadRcdSummaryByTierPieChart([
                                [''],
                                [0]
                            ]);
                        }
                    }
                    tplDashboardStatsBarChart.hideLoading();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    tplDashboardStatsBarChart.hideLoading();
                    console.log(textStatus, errorThrown);
                }
            });
        });

        checkSelectedTeam();

        function checkSelectedTeam () {
        const selectedTeam = $('#team-filter').val();
        if (selectedTeam && selectedTeam.length > 0) {
            fetchTeamUsers(selectedTeam);
        }
    }

        function fetchTeamUsers(teams) {
            $.ajax({
                url: "/get-users-by-team",
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
                        if (users.length > 0) {
                            agentFilter.enable();
                            agentFilter.setData(users);
                        } else {
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
        $('#commercial-filter').on('change', function(e) {
            var commercialFilterValue = $('#commercial-filter').val();

            tplDashboardStatsBarChart.showLoading();
            $.ajax({
                url: "/get-tpl-filter-stats",
                type: "post",
                data: {
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
                            tplDashboardStatsBarChart.destroy();
                            createLeadRcdSummaryByTierPieChart([labels, numbers]);
                        } else {
                            tplDashboardStatsBarChart.destroy();
                            createLeadRcdSummaryByTierPieChart([
                                [''],
                                [0]
                            ]);
                        }
                    }
                    tplDashboardStatsBarChart.hideLoading();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    comprehensiveDashboardStatChart.hideLoading();
                    console.log(textStatus, errorThrown);
                }
            });
        });
    });

    new SlimSelect({
        select: '#commercial-filter',
        settings: {
            allowDeselect: false,
            placeholderText: 'Commercial filter',
        }
    });

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
        @endcan
        <div class="md:w-1/4">
            <label>Advisor</label>
            <select multiple name="users[]" id="user-filter">
                <option data-placeholder="true"></option>

            </select>
        </div>
        <div class="md:w-1/4">
            <label>Commercial</label>
            <select name="isCommercial" id="commercial-filter" placeholder="Select any option">
                <option data-placeholder="true"></option>
                <option value="All" selected>All</option>
                <option value="true">Yes</option>
                <option value="false">No</option>
            </select>
        </div>

    </div>
    <div style="min-height: 700px;">
        <div id="tplConversionDiv"></div>
    </div>
</div>
@endsection
