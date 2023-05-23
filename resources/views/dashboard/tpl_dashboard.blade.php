@extends('layouts.app_livewire')
@section('title','TPL Dashboard')
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
                        format: '{point.y:.1f}%'
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
            }]
        });

    }
    $(function() {
        createLeadRcdSummaryByTierPieChart(tplDashboardStats);
        $('#tier-filter, #source-filter, #team-filter').on('change', function(e) {
            var tierFilterValue = $('#tier-filter').val();
            var sourceFilterValue = $('#source-filter option:selected').val();
            var teamFilterValue = $('#team-filter').val();
            tplDashboardStatsBarChart.showLoading();
            $.ajax({
                url: "/get-tpl-filter-stats",
                type: "post",
                data: {
                    'tier_filter': tierFilterValue,
                    'team_filter': teamFilterValue,
                    'source': sourceFilterValue
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
    });

    new SlimSelect({
        select: '#tier-filter',
        settings: {
            allowDeselect: true,
            placeholderText: 'Select Tier',
        }
    })

    new SlimSelect({
        select: '#team-filter',
        settings: {
            allowDeselect: true,
            placeholderText: 'Select Team',
        }
    })
</script>
@endpush

<div>
    <div class="flex gap-4 justify-end mb-4">
        <div class="md:w-1/4">
            <select multiple name="tiers[]" id="tier-filter">
                <option data-placeholder="true"></option>
                @foreach ($tiers as $tier)
                <option value={{$tier->id}}>{{$tier->name}}</option>
                @endforeach
            </select>
        </div>
        <div class="md:w-1/4">
            <select multiple name="teams[]" id="team-filter">
                <option data-placeholder="true"></option>
                @foreach ($teams as $team)
                <option @if($commonTeam==$team->id) selected="selected" @endif value="{{$team->id}}">{{$team->name}}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div style="min-height: 700px;">
        <div id="tplConversionDiv"></div>
    </div>
</div>
@endsection
