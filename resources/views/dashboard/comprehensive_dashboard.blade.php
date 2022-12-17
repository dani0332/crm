@extends('layouts.app_livewire')
@section('title','Comprehensive Dashboard')
@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-2">
            <div class="panel-heading" style="font-size: 30px; font-wieght: 800;float:right;">
                <select
                    class="inline-flex w-full justify-center pr-10 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-100"
                    id="userFilter" style="margin-left: 10px;">
                    <option value="">All Users</option>
                    @foreach ($carUsers as $carUser)
                    <option value="{{$carUser->id}}"> {{ $carUser->name }} </option>
                    @endforeach
                </select>
            </div>
            <div class="panel-heading" style="font-size: 30px; font-wieght: 800;float:right;">
                <select
                    class="inline-flex w-full justify-center pr-10 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-100"
                    id="tier-filter">
                    <option value="">Select Tier</option>
                    @foreach ($tiers as $tier)
                    <option value="{{$tier->id}}"> {{ $tier->name }} </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div style="clear: both;"></div>
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div id="comprehensiveConversion"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>
<script>
    var comprehensiveDashboardStatChart = {};
    var comprehensiveDashboardStats = <?php echo json_encode($comprehensiveDashboardStats)?>;
    function createComprehensiveConversionChart(comprehensiveDashboardStats)
    {
        var data = [];
        for (let index = 0; index < comprehensiveDashboardStats[0].length; index++) {
            data.push({name : comprehensiveDashboardStats[0][index] , y: parseFloat(comprehensiveDashboardStats[1][index])})
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
                        format: '{point.y:.1f}%'
                    }
                }
            },

            tooltip: {
                headerFormat: '<span style="font-size:11px">{series.name}</span><br>',
                pointFormat: '<span style="color:{point.color}">{point.name}</span>: <b>{point.y:.2f}%</b>'
            },

            series: [
                {
                    name: 'Net Conversion',
                    colorByPoint: true,
                    data: data
                }
            ]
        });

    }
    $(function(){
        createComprehensiveConversionChart(comprehensiveDashboardStats);
        $('#tier-filter,#userFilter').on('change', function (e) {
            var tierFilterValue = $('#tier-filter option:selected').val();
            var userFilterValue = $('#userFilter option:selected').val();
            $.get('/get-comp-filter-stats?tier_filter=' + tierFilterValue + '&userFilter='+ userFilterValue, function (result) {
               if(result){
                var labels = (typeof result[0]) == 'string' ? JSON.parse(result[0]) : result[0];
                var data = (typeof result[1]) == 'string' ? JSON.parse(result[1]) : result[1];
                var numbers = [];
                for (let index = 0; index < data.length; index++) {
                    numbers.push(parseFloat(data[index]));
                }
                if(labels.length > 0 ){
                    comprehensiveDashboardStatChart.destroy();
                    createComprehensiveConversionChart([labels,numbers]);
                }else{
                    comprehensiveDashboardStatChart.destroy();
                    createComprehensiveConversionChart([[''], [0]]);
                }
               }
            });
        });
    });
</script>
@endsection
