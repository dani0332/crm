@extends('layouts.app_livewire')
@section('title','TPL Dashboard')
@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading" style="font-size: 30px; font-wieght: 800;float:left;">TPL Conversion </div>
                <div class="panel-heading" style="font-size: 30px; font-wieght: 800;float:right;">
                    <select class="inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-100" id="tier-filter">
                        <option value="" onchange="">Select Tier</option>
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
    $(function(){
        $('#tier-filter').on('change', function (e) {
            var tierFilterValue = $('#tier-filter option:selected').val();
            debugger;
            $.get('/tpl-conversion-dashboard?tier_filter=' + tierFilterValue, function (data) {
                debugger;
            });
        });
    });
    function randomRGB() {
        var roundValue = Math.round, rndmValue = Math.random, maxNum = 255;
        return 'rgba(' + roundValue(rndmValue()*maxNum) + ',' + roundValue(rndmValue()*maxNum) + ',' + roundValue(rndmValue()*maxNum) + ')';
    }
    var backgroundColors = [];
    for (let index = 0; index < 50; index++) {
        backgroundColors.push(randomRGB());
    }
    var labels = <?php echo $labels; ?>;
    var data = <?php echo $data; ?>;
    const ctx = document.getElementById('myChart');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Net Conversion',
          data: data,
          borderWidth: 1,
          borderColor: '#2989CB',
          backgroundColor: [
            'rgba(255, 99, 132, 0.2)',
            'rgba(255, 159, 64, 0.2)',
            'rgba(255, 205, 86, 0.2)',
            'rgba(75, 192, 192, 0.2)',
            'rgba(54, 162, 235, 0.2)',
            'rgba(153, 102, 255, 0.2)',
            'rgba(201, 203, 207, 0.2)'
        ],
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
</script>
@endsection
