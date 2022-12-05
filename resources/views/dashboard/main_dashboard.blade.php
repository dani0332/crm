@extends('layouts.app_livewire')
@section('title','Accumulative Dashboard')
@section('content')
<style>
    * {
        box-sizing: border-box;
    }

    .slider {
        width: 600px;
        text-align: center;
        overflow: hidden;
    }

    .slides {
        display: flex;
        overflow-x: auto;
        border-radius: 5px;
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
        scroll-snap-points-x: repeat(300px);
        scroll-snap-type: mandatory;
    }

    .slides::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    .slides::-webkit-scrollbar-thumb {
        background: black;
        border-radius: 10px;
    }

    .slides::-webkit-scrollbar-track {
        background: transparent;
    }

    .slides > div {
        padding: 15px;
        flex-shrink: 0;
        flex-direction: column;
        width: 600px;
        height: 300px;
        border-radius: 10px;
        background: #eee;
        transform-origin: center center;
        transform: scale(1);
        transition: transform 0.5s;
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 45px;
        margin-right: 15px;
    }

    .slides > div:target {
        transform: scale(0.8);
    }

    .slider > a {
        display: inline-flex;
        width: 1.5rem;
        height: 1.5rem;
        background: white;
        text-decoration: none;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        margin: 0 0 0.5rem 0;
        position: relative;
    }

    .slider > a:active {
        top: 1px;
    }

    .slider > a:focus {
        background: #000;
    }

    /* Don't need button navigation */
    @supports (scroll-snap-type) {
        .slider > a {
            display: none;
        }
    }
</style>
<div class="container">
    <div class="row">
        <div class="col-md-4" style="float: left;">
            <div class="slider">
                <div class="slides">
                    <div id="slide-1">
                        <div style="font-size: 35px;">
                            TOTAL LEADS RECEIVED
                        </div>
                        <div>
                            <b>546</b>
                        </div>
                    </div>
                    <div id="slide-2">
                        <div>
                            TOTAL LEADS RECEIVED ECOMMERCE
                        </div>
                        <div>
                            <b>546</b>
                        </div>
                    </div>
                    <div id="slide-3">
                        <div>
                            TOTAL UNASSIGNED LEADS
                        </div>
                        <div>
                            <b>546</b>
                        </div>
                    </div>
                    <div id="slide-4">
                        <div>
                            TOTAL UNASSIGNED LEADS ECOMMERCE
                        </div>
                        <div>
                            <b>546</b>
                        </div>
                    </div>
                </div>
                <a href="#slide-1">1</a>
                <a href="#slide-2">2</a>
                <a href="#slide-3">3</a>
                <a href="#slide-4">4</a>
            </div>
        </div>
        <div class="col-md-4" style="float: left;">

        </div>
        <div class="col-md-4" style="float: right;">
            <div class="slider">
                <div class="slides">
                    <div id="slide-5">
                        <div style="font-size: 35px;">
                            TOTAL LEADS RECEIVED
                        </div>
                        <div>
                            <b>546</b>
                        </div>
                    </div>
                    <div id="slide-6">
                        <div>
                            TOTAL LEADS RECEIVED ECOMMERCE
                        </div>
                        <div>
                            <b>546</b>
                        </div>
                    </div>
                    <div id="slide-7">
                        <div>
                            TOTAL UNASSIGNED LEADS
                        </div>
                        <div>
                            <b>546</b>
                        </div>
                    </div>
                    <div id="slide-8">
                        <div>
                            TOTAL UNASSIGNED LEADS ECOMMERCE
                        </div>
                        <div>
                            <b>546</b>
                        </div>
                    </div>
                </div>
                <a href="#slide-5">1</a>
                <a href="#slide-6">2</a>
                <a href="#slide-7">3</a>
                <a href="#slide-8">4</a>
            </div>
        </div>
    </div>
    <div style="clear: both;">
    <div class="row mt-12">
        <div class="col-md-6 col-md-offset-1" style="width: 500px; float:left;">
            <div class="panel panel-default">
                <div class="panel-body">
                    <canvas id="myChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-md-offset-1" style="width: 500px; float:right;">
            <div class="panel panel-default">
                <div class="panel-body">
                    <canvas id="1myChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div style="clear: both;">
    </div>
    <div class="row">
        <h1 class="text-2xl text-left mb-14 mt-12 text-[#308BCA]">
            Advisor Conversion Report
          </h1>
          @livewire('advisor-conversion-report-table')
    </div>
    <div style="clear: both;">
    </div>
    <div class="row mt-12">
        <div class="col-md-12 col-md-offset-1" style="width: 500px; float:left;">
            <div class="panel panel-default">
                <div class="panel-body">
                    <canvas id="2myChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div style="clear: both;">
    </div>

    <div class="row mt-12">
        <div class="col-md-12 col-md-offset-1" style="width: 500px; float:left;">
            <div class="panel panel-default">
                <div class="panel-body">
                    <canvas id="3myChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div style="clear: both;">
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
    function initializeAChart(labels, data, id)
    {
        var co = id ;
        var ctr = document.getElementById(co);
        tpl_conversion_chart = new Chart(ctr, {
            type: 'pie',
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
    function initializeBChart(labels, data, id)
    {
        var co = id ;
        var ctx = document.getElementById(co);
        tpl_conversion_chart = new Chart(ctx, {
            type: 'pie',
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
                    initializeAChart(labels, data, 'myChart');
                    initializeBChart(labels, data, '1myChart');
                }else{
                    tpl_conversion_chart.destroy();
                    initializeAChart([''], [0], 'myChart');
                    initializeBChart(labels, data, '1myChart');
                }
               }
            });
        });
    });
    var labels = <?php echo $labels; ?>;
    var data = <?php echo $data; ?>;
    initializeAChart(labels, data, 'myChart');
    initializeBChart(labels, data, '1myChart');
</script>
@endsection
