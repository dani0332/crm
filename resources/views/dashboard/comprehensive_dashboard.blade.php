@extends('layouts.app_livewire')
@section('title','Comprehensive Dashboard')
@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading" style="font-size: 30px; font-wieght: 800;float:left;">Comprehensive Conversion </div>
                <div class="panel-heading" style="font-size: 30px; font-wieght: 800;float:right;">
                    <select
                        class="inline-flex w-full justify-center pr-10 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-100"
                        id="userFilter" style="margin-left: 10px;">
                        <option value="">All Users</option>
                        @foreach ($carUsers as $carUser)
                            <option value="{{$carUser->id}}" > {{ $carUser->name }} </option>
                        @endforeach
                    </select>
                </div>
                <div class="panel-heading" style="font-size: 30px; font-wieght: 800;float:right;">
                    <select
                        class="inline-flex w-full justify-center pr-10 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-100"
                        id="tier-filter">
                        <option value="">Select Tier</option>
                        @foreach ($tiers as $tier)
                            <option value="{{$tier->id}}" > {{ $tier->name }} </option>
                        @endforeach
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
    var backgroundColors =  [
                                'rgba(255, 99, 132, 0.2)',
                                'rgba(255, 159, 64, 0.2)',
                                'rgba(255, 205, 86, 0.2)',
                                'rgba(75, 192, 192, 0.2)',
                                'rgba(54, 162, 235, 0.2)',
                                'rgba(153, 102, 255, 0.2)',
                                'rgba(201, 203, 207, 0.2)'
                            ];
    function initializeChart(labels, data)
    {
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
                    beginAtZero: true
                }
                }
            }
        });
    }
    $(function(){
        $('#tier-filter,#userFilter').on('change', function (e) {
            var tierFilterValue = $('#tier-filter option:selected').val();
            var sourceFilterValue = $('#userFilter option:selected').val();
            $.get('/get-comp-filter-stats?tier_filter=' + tierFilterValue + '&userFilter='+ sourceFilterValue, function (data) {
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
