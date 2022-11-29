@extends('layouts.app_livewire')
@section('title','TPL Dashboard')
@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading" style="font-size: 30px; font-wieght: 800;">TPL Conversion </div>
                <div class="panel-body">
                    <canvas id="myChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
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
