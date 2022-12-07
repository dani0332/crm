@extends('layouts.app_livewire')
@section('title','TPL Dashboard')
@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading" style="font-size: 30px; font-wieght: 800;float:left;">TPL Conversion </div>
                <div class="panel-heading" style="font-size: 30px; font-wieght: 800;float:right;">
                    <select
                        class="inline-flex w-full justify-center pr-10 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-100"
                        id="source-filter" style="margin-left: 10px;">
                        <option value="">Exclude manual created leads</option>
                        <option value="yes">Yes</option>
                        <option value="no">No</option>
                    </select>
                </div>
                <div class="panel-heading" style="font-size: 30px; font-wieght: 800;float:right;">
                    <select
                        class="inline-flex w-full justify-center pr-10 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-100"
                        id="tier-filter">
                        <option value="">Select Tier</option>
                        <option value="tr">Tier R</option>
                        <option value="t6">Tier 6</option>
                    </select>
                </div>
                <div class="panel-body">
                    <canvas id="myChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    var tpl_conversion_chart = {};
    var backgroundColors =   ['#FFBF00', '#DE3163', '#40E0D0', '#7B68EE', '#FF7F50', '#50C878', '#6495ED', '#F06292', '#4DD0E1'];
    function initializeChart(labels, data)
    {
        const percentages = [];
        for (let i = 0; i < data.length; i++) {
            percentages.push(data[i] + ' %');
        }
        const ctx = document.getElementById('myChart');
        tpl_conversion_chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                label: 'Net Conversion',
                data: data,
                borderWidth: 1,
                borderColor: '#2989CB',
                backgroundColor: backgroundColors,
                }]
            },
            options: {
                scales: {
                    y: {
                        ticks: {
                            // Include a dollar sign in the ticks
                            callback: function(value, index, ticks) {
                                return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + ' % ' ;
                            }
                        },
                        beginAtZero: true,
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';

                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y + ' %';
                                }
                                return label;
                            },
                            labelPointStyle: function(context) {
                                return {
                                    pointStyle: 'triangle',
                                    rotation: 0
                                };
                            }
                        },
                        usePointStyle: true,
                    }
                }
            },
        });
    }
    $(function(){
        $('#tier-filter,#source-filter').on('change', function (e) {
            var tierFilterValue = $('#tier-filter option:selected').val();
            var sourceFilterValue = $('#source-filter option:selected').val();
            $.get('/get-tpl-filter-stats?tier_filter=' + tierFilterValue + '&source='+ sourceFilterValue, function (data) {
               if(data){
                var labels = (typeof data[0]) == 'string' ? JSON.parse(data[0]) : data[0];
                var data = (typeof data[1]) == 'string' ? JSON.parse(data[1]) : data[1];
                if(labels.length > 0 ){
                    tpl_conversion_chart.destroy();
                    initializeChart(labels, data);
                }else{
                    tpl_conversion_chart.destroy();
                    initializeChart([''], [0]);
                }
               }
            });
        });
    });
    var labels = <?php echo $labels; ?>;
    var data = <?php echo $data; ?>;
    initializeChart(labels, data);
</script>
@endsection
