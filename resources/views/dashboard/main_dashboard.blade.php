@extends('layouts.app_livewire')
@section('title','Accumulative Dashboard')
@section('content')
<style>

    * {
        box-sizing: border-box;
    }

    .slider {
        width: 400px;
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

    .slides>div {
        flex-shrink: 0;
        flex-direction: column;
        width: 400px;
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

    .slides>div:target {
        transform: scale(0.8);
    }

    .slider>a {
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

    .slider>a:active {
        top: 1px;
    }

    .slider>a:focus {
        background: #000;
    }

    /* Don't need button navigation */
    @supports (scroll-snap-type) {
        .slider>a {
            display: none;
        }
    }
    .corder {
        border : 1px solid black;
        padding: 5px;
        margin: 10px;
        width: 40%;
        box-shadow: rgba(0, 0, 0, 0.17) 0px -23px 25px 0px inset, rgba(0, 0, 0, 0.15) 0px -36px 30px 0px inset, rgba(0, 0, 0, 0.1) 0px -79px 40px 0px inset, rgba(0, 0, 0, 0.06) 0px 2px 1px, rgba(0, 0, 0, 0.09) 0px 4px 2px, rgba(0, 0, 0, 0.09) 0px 8px 4px, rgba(0, 0, 0, 0.09) 0px 16px 8px, rgba(0, 0, 0, 0.09) 0px 32px 16px;
    }

    .oddDiv {
        float: left;
        font-size: 20px;
        text-align: center;
    }
    .evenDiv {
        float: right;
        text-align: center;
        font-size: 20px;
    }

</style>


<div class="container">
    <div class="row">
        <div class="col-md-6" style="float: left;width:50%;padding: 10px;">
            <div class="col-md-12">
                <div class="col-md-2 corder oddDiv" style="background-color: #EF5445; color: white;border-radius: 25px;">
                    T. LEADS RCVD
                    <div style="text-align: center;">
                        <b>{{$totalLeadsReceived}}</b>
                    </div>
                </div>
                <div class="col-md-2 corder evenDiv" style="background-color: #2F78E2; color: white;border-radius: 25px;">
                    T. LEADS RCVD ECOM
                    <div style="text-align: center;">
                        <b>{{$totalLeadsReceivedEcommerce}}</b>
                    </div>
                </div>
                <div class="col-md-2 corder oddDiv" style="background-color: #DF2EE6; color: white;border-radius: 25px;">
                    T. UNASSIGNED LEADS
                    <div style="text-align: center;">
                        <b>{{$totalUnAssignedLeadsReceived}}</b>
                    </div>
                </div>
                <div class="col-md-2 corder evenDiv" style="background-color: #49D1B8; color: white;border-radius: 25px;">
                    T. UNASSIGNED LEADS ECOM
                    <div style="text-align: center;">
                        <b>{{$totalUnAssignedLeadsReceivedEcommerce}}</b>
                    </div>
                </div>
                <div class="col-md-4 corder oddDiv" style="background-color: #EF5445; color: white;border-radius: 25px;">
                    T. UNASSIGNED REVIVAL LEADS
                    <div style="text-align: center;">
                        <b>{{$totalUnAssignedLeadsReceivedEcommerce}}</b>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6" style="float: left;width:50%;padding: 10px;">
            <div class="col-md-12">
                @foreach ($teamWiseLeadsAssignedAverage as $item)
                    @php
                        $backGroundColor = ['#FF7F50', '#22c55e','#ef4444', '#0c4a6e', '#0ea5e9', '#fbbf24','#0369a1'];
                    @endphp
                    <div class="col-md-1" style="margin-top:10px; margin-left:10px; float: left;border-radius: 10px;border: 1px solid black;padding: 10px;text-align: center;color:black;box-shadow: rgba(50, 50, 93, 0.25) 0px 30px 60px -12px inset, rgba(0, 0, 0, 0.3) 0px 18px 36px -18px inset;">
                        {{$item['teamName']}}
                        <div style="text-align: center;">
                            <b>{{ $item['totalLeadsCount'] .' / '. $item['totalUsersUnderTeam']. ' = ' . number_format((float)$item['totalLeadsCount'] / $item['totalUsersUnderTeam'], 2, '.', '')  }}</b>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        {{-- <div class="col-md-4" style="float: left;width:33%;padding: 10px;">
            <div class="col-md-12">
                <div class="col-md-3 corder">
                    First
                </div>
                <div class="col-md-3 corder">
                    Second
                </div>
                <div class="col-md-3 corder">
                    Third
                </div>
                <div class="col-md-3 corder">
                    Four
                </div>
            </div>

        </div> --}}
    </div>
    {{-- <div class="row">
        <div class="col-md-4" style="float: left;">
            <div class="slider">
                <div class="slides">
                    <div id="slide-1">
                        <div style="font-size: 30px;">
                            TOTAL LEADS RECEIVED
                        </div>
                        <div>
                            <b>{{$totalLeadsReceived}}</b>
                        </div>
                    </div>
                    <div id="slide-2">
                        <div style="font-size: 30px;">
                            TOTAL LEADS RECEIVED ECOMMERCE
                        </div>
                        <div>
                            <b>{{$totalLeadsReceivedEcommerce}}</b>
                        </div>
                    </div>
                    <div id="slide-3">
                        <div style="font-size: 30px;">
                            TOTAL UNASSIGNED LEADS
                        </div>
                        <div>
                            <b>{{$totalUnAssignedLeadsReceived}}</b>
                        </div>
                    </div>
                    <div id="slide-4">
                        <div style="font-size: 30px;">
                            TOTAL UNASSIGNED LEADS ECOMMERCE
                        </div>
                        <div>
                            <b>{{$totalUnAssignedLeadsReceivedEcommerce}}</b>
                        </div>
                    </div>
                </div>
                <a href="#slide-1">1</a>
                <a href="#slide-2">2</a>
                <a href="#slide-3">3</a>
                <a href="#slide-4">4</a>
            </div>
        </div>
        <div class="col-md-4" style="margin-left: 50px;float: left;">
            <div class="slider">
                <div class="slides">
                    @php
                        $teamAverageCount = 0;
                    @endphp
                @foreach ($teamWiseLeadsAssignedAverage as $item)

                       @php
                            $teamAverageCount++;
                        @endphp
                    <div id="{{'slide-'.$teamAverageCount + 100}}">
                        <div style="font-size: 30px;">
                            {{$item['teamName']}}
                        </div>
                        <div>
                            <b>{{ $item['totalLeadsCount'] .' / '. $item['totalUsersUnderTeam']. ' = ' . number_format((float)$item['totalLeadsCount'] / $item['totalUsersUnderTeam'], 2, '.', '')  }}</b>
                        </div>
                    </div>
                @endforeach
                </div>
                @php
                    $teamSilderAnchorCount = 0;
                @endphp
                @foreach ($teamWiseLeadsAssignedAverage as $item)
                    @php
                         $teamSilderAnchorCount++;
                    @endphp
                    <a href="{{'#slide-'. $teamSilderAnchorCount + 100}}">{{$teamSilderAnchorCount}}</a>

                @endforeach
            </div>
        </div>
        <div class="col-md-4" style="float: left;margin-left: 50px;">
            <div class="slider">
                <div class="slides">
                    <div id="slide-5">
                        <div style="font-size: 30px;">
                            TOTAL LEADS RECEIVED
                        </div>
                        <div>
                            <b>{{$totalLeadsReceived}}</b>
                        </div>
                    </div>
                    <div id="slide-6">
                        <div style="font-size: 30px;">
                            TOTAL LEADS RECEIVED ECOMMERCE
                        </div>
                        <div>
                            <b>{{$totalLeadsReceivedEcommerce}}</b>
                        </div>
                    </div>
                    <div id="slide-7">
                        <div style="font-size: 30px;">
                            TOTAL UNASSIGNED LEADS
                        </div>
                        <div>
                            <b>{{$totalUnAssignedLeadsReceived}}</b>
                        </div>
                    </div>
                    <div id="slide-8">
                        <div style="font-size: 30px;">
                            TOTAL UNASSIGNED LEADS ECOMMERCE
                        </div>
                        <div>
                            <b>{{$totalUnAssignedLeadsReceivedEcommerce}}</b>
                        </div>
                    </div>
                </div>
                <a href="#slide-5">1</a>
                <a href="#slide-6">2</a>
                <a href="#slide-7">3</a>
                <a href="#slide-8">4</a>
            </div>
        </div>
    </div> --}}
    <div style="clear: both;">
        <div class="row mt-12">
            <div class="col-md-6 ml-10  col-md-offset-1" style="width: 700px; float:left;">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <h1 class="text-xl text-left mb-14 mt-12 text-[#308BCA]">
                            Total Leads Received Summary (by tier)
                        </h1>
                        <canvas id="myChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6 ml-10 col-md-offset-1" style="width: 700px; float:right;">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <h1 class="text-xl text-left mb-14 mt-12 text-[#308BCA]">
                            Unassigned Leads Received Summary (by tier)
                        </h1>
                        <canvas id="1myChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div style="clear: both;">
        </div>
        <div class="row mt-12">
            <div class="col-md-6 ml-10 col-md-offset-1" style="width: 700px; float:left;margin-left: 50px;">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <h1 class="text-xl text-left mb-14 mt-12 text-[#308BCA]">
                            Unassigned Leads Received Summary (by LeadSource)
                        </h1>
                        <canvas id="2myChart"></canvas>
                    </div>
                </div>
            </div>

        </div>
        <div style="clear: both;">
        </div>
        <div class="row mt-12">
            <h1 class="text-xl text-left mb-14 mt-12 text-[#308BCA]">
                Total Leads Received Summary (by LeadSource)
                @livewire('lead-received-summary-by-source-data-table')
            </h1>

        </div>
        <div style="clear: both;">
        </div>
        <div class="row mt-12">
            <div class="col-md-12 col-md-offset-1" style="width: 600px; float:left;">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <canvas id="2myChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <h1 class="text-xl text-left mb-14 mt-12 text-[#308BCA]">
                Advisor Conversion Report
            </h1>
            <div class="col-md-3" style="float: right;flex-direction: column;">
                <select
                    class="inline-flex w-full justify-center pr-10 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-100"
                    id="source-filter" style="margin-left: 10px;">
                    <option value="">Select Advisor</option>
                    @foreach ($carAdvisors as $team)
                    <option value="{{$team->id}}">{{$team->name}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-9">
                <div class="panel-body">
                    <canvas id="3myChart"></canvas>
                </div>
            </div>
        </div>
        <div style="clear: both;">
        </div>
        <div class="row">
            <h1 class="text-xl text-left mb-14 mt-12 text-[#308BCA]">
                Lead Assign Count Summary Per Advisor
            </h1>
            <div class="col-md-3" style="float: right;flex-direction: column;">
                <select
                    class="inline-flex w-full justify-center pr-10 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-100"
                    id="source-filter" style="margin-left: 10px;">
                    <option value="">Select Team</option>
                    @foreach ($teams as $team)
                    <option value="{{$team->id}}">{{$team->name}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-9">
                <div class="panel-body">
                    <canvas id="4myChart"></canvas>
                </div>
            </div>
        </div>
        <div style="clear: both;">
        </div>
    </div>
    <script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-piechart-outlabels@0.1.4/dist/chartjs-plugin-piechart-outlabels.min.js"></script>

    <script>
    var tpl_conversion_chart = {};
    var backgroundColors =  ['#FFBF00', '#DE3163', '#40E0D0', '#7B68EE', '#FF7F50', '#50C878', '#6495ED', '#F06292', '#4DD0E1'];
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
                color: 'black',
                }]
            },
            options: {
            plugins: {
                legend: true,
                outlabels: {
                    text: '%v %',
                    color: 'white',
                    stretch: 20,
                    valuePrecision: 0,
			        percentPrecision: 2,
                    font: {
                        resizable: true,
                        minSize: 12,
                        maxSize: 18
                    }
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
    function initializeCChart(labels, data, id)
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
    function initializeDChart(labels, data, id)
    {
        var co = id ;
        var ctx = document.getElementById(co);
        tpl_conversion_chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                label: 'Net Conversion',
                data: data,
                borderWidth: 1,
                borderColor: '#2989CB',
                backgroundColor: ['#FF7F50', '#22c55e','#ef4444', '#0c4a6e', '#0ea5e9', '#fbbf24','#0369a1'],
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
    function initializeEChart(labels, data, id)
    {
        var co = id ;
        var ctx = document.getElementById(co);
        tpl_conversion_chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                label: 'Net Conversion',
                data: data,
                borderWidth: 1,
                borderColor: '#2989CB',
                backgroundColor: '#95CEFF',
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
                    initializeBChart(labels, data, '2myChart');
                    initializeDChart(labels, data, '3myChart');
                    initializeEChart(labels, data, '4myChart');
                }else{
                    tpl_conversion_chart.destroy();
                    initializeAChart([''], [0], 'myChart');
                    initializeBChart(labels, data, '1myChart');
                    initializeBChart(labels, data, '2myChart');
                    initializeDChart(labels, data, '3myChart');
                    initializeEChart(labels, data, '4myChart');
                }
               }
            });
        });
    });
    var labels = <?php echo $labels; ?>;
    var data = <?php echo $data; ?>;
    var advisorConversionLabels = <?php echo $advisorConversionLabels; ?>;
    var advisorConversionData = <?php echo $advisorConversionData; ?>;
    initializeAChart(labels, data, 'myChart');
    initializeBChart(labels, data, '1myChart');
    initializeBChart(labels, data, '2myChart');
    initializeDChart(labels, data, '3myChart');
    initializeEChart(advisorConversionLabels, advisorConversionData, '4myChart');
    </script>
    @endsection
