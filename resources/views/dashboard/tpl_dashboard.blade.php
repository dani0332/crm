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
            }]
        });

    }
    $(function() {
        createLeadRcdSummaryByTierPieChart(tplDashboardStats);
    });

</script>
@endpush

<div>
    <div style="min-height: 700px;">
        <div id="tplConversionDiv"></div>
    </div>
</div>
@endsection
