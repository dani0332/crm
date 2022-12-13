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
        border: 1px solid black;
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


    .highcharts-figure,
.highcharts-data-table table {
    min-width: 320px;
    max-width: 800px;
    margin: 1em auto;
}

.highcharts-data-table table {
    font-family: Verdana, sans-serif;
    border-collapse: collapse;
    border: 1px solid #ebebeb;
    margin: 10px auto;
    text-align: center;
    width: 100%;
    max-width: 500px;
}

.highcharts-data-table caption {
    padding: 1em 0;
    font-size: 1.2em;
    color: #555;
}

.highcharts-data-table th {
    font-weight: 600;
    padding: 0.5em;
}

.highcharts-data-table td,
.highcharts-data-table th,
.highcharts-data-table caption {
    padding: 0.5em;
}

.highcharts-data-table thead tr,
.highcharts-data-table tr:nth-child(even) {
    background: #f8f8f8;
}

.highcharts-data-table tr:hover {
    background: #f1f7ff;
}

input[type="number"] {
    min-width: 50px;
}

</style>


<div class="container">
    <div class="row">
        <div class="col-md-6" style="float: left;width:50%;padding: 10px;">
            <div class="col-md-12">
                <div class="col-md-2 corder oddDiv"
                    style="background-color: #EF5445; color: white;border-radius: 25px;">
                    LEADS RCVD
                    <div style="text-align: center;">
                        <b>{{$totalLeadsReceived}}</b>
                    </div>
                </div>
                <div class="col-md-2 corder evenDiv"
                    style="background-color: #2F78E2; color: white;border-radius: 25px;">
                    LEADS RCVD ECOM
                    <div style="text-align: center;">
                        <b>{{$totalLeadsReceivedEcommerce}}</b>
                    </div>
                </div>
                <div class="col-md-2 corder oddDiv"
                    style="background-color: #DF2EE6; color: white;border-radius: 25px;">
                    UNASSIGNED LEADS
                    <div style="text-align: center;">
                        <b>{{$totalUnAssignedLeadsReceived}}</b>
                    </div>
                </div>
                <div class="col-md-2 corder evenDiv"
                    style="background-color: #49D1B8; color: white;border-radius: 25px;">
                    UNASSIGNED LEADS ECOM
                    <div style="text-align: center;">
                        <b>{{$totalUnAssignedLeadsReceivedEcommerce}}</b>
                    </div>
                </div>
                <div class="col-md-4 corder oddDiv"
                    style="background-color: #EF5445; color: white;border-radius: 25px;">
                    UNASSIGNED REVIVAL LEADS
                    <div style="text-align: center;">
                        <b>{{$totalUnAssignedRevivalLeads}}</b>
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
                <div class="col-md-1"
                    style="margin-top:10px; margin-left:10px; float: left;border-radius: 10px;border: 1px solid black;padding: 10px;text-align: center;color:black;box-shadow: rgba(50, 50, 93, 0.25) 0px 30px 60px -12px inset, rgba(0, 0, 0, 0.3) 0px 18px 36px -18px inset;">
                    {{$item['teamName']}}
                    <div style="text-align: center;">
                        <b>{{ $item['totalLeadsCount'] .' / '. $item['totalUsersUnderTeam']. ' = ' .
                            number_format((float)$item['totalLeadsCount'] / $item['totalUsersUnderTeam'], 2, '.', '')
                            }}</b>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    <div style="clear: both;"></div>
    <div class="row mt-12">
        <div class="col-md-6 ml-10  col-md-offset-1" style="width: 700px; float:left;">
            <div class="panel panel-default">
                <div class="panel-body">
                    <h1 class="text-xl text-left mb-14 mt-12 text-[#308BCA]">

                    </h1>
                    <div id="LeadRcdSummaryByTier"></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 ml-10 col-md-offset-1" style="width: 700px; float:right;">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div id="UnAssignedLeadRcdSummaryByTier" style="margin-top: 60px;"></div>
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
                    <div id="UnAssignedLeadRcdSummaryByLeadSource" style="margin-top: 60px;"></div>
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
    <div class="row">
        <div class="col-md-3" style="float: right;">
            <select
                class="inline-flex w-full justify-center pr-10 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-100"
                id="source-filter" style="margin-left: 10px;">
                <option value="">Select Advisor</option>
                @foreach ($carAdvisors as $team)
                <option value="{{$team->id}}">{{$team->name}}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12" style="margin-top: 80px;">
            <div class="panel-body">
                <div id="advisorConversion"></div>
            </div>
        </div>
    </div>
    <div style="clear: both;"  style="margin-bottom: 40px;">
    </div>
    <div class="row" style="margin-top: 40px;">
        <div class="col-md-3" style="float: right;">
            <select
                class="inline-flex w-full justify-center pr-10 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-100"
                id="source-filter" style="margin-left: 10px;">
                <option value="">Select Team</option>
                @foreach ($teams as $team)
                <option value="{{$team->id}}">{{$team->name}}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12" style="margin-top: 80px;">
            <div class="panel-body">
                <div id="leadAssignCountSummaryByAdvisor"></div>
            </div>
        </div>
    </div>
    <div style="clear: both;">
    </div>
</div>
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>
<script type="text/javascript">
    var data = <?php echo json_encode($data)?>;
    var labels = <?php echo json_encode($labels)?>;

   $(function(){
        createLeadRcdSummaryByTierPieChart(data, labels);
        createUnAssignedLeadRcdSummaryByTierChart(data, labels);
        createUnAssignedLeadRcdSummaryByLeadSourceChart(data,labels);
        createAdvisorConversionChart(data, labels);
        createLeadAssignCountSummaryByAdvisorChart(data,labels);
   });


   function createLeadRcdSummaryByTierPieChart(data, labels)
   {
        var cData = [];
        for (let index = 0; index < data.length; index++) {
            cData.push({name: labels[index], y: parseFloat(data[index])});
        }
            // Data retrieved from https://netmarketshare.com
        Highcharts.chart('LeadRcdSummaryByTier', {
            chart: {
                plotBackgroundColor: null,
                plotBorderWidth: null,
                plotShadow: false,
                type: 'pie'
            },
            title: {
                text: 'Total Leads Received Summary (by tier)',
                align: 'center'
            },
            tooltip: {
                pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
            },
            accessibility: {
                point: {
                    valueSuffix: '%'
                }
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true,
                        format: '<b>{y} Leads'
                    }
                }
            },
            series: [{
                name: 'Leads',
                colorByPoint: true,
                data: cData
            }]
        });

   }

   function createUnAssignedLeadRcdSummaryByTierChart(data, labels)
   {
        var cData = [];
        for (let index = 0; index < data.length; index++) {
            cData.push({name: labels[index], y: parseFloat(data[index])});
        }
        Highcharts.chart('UnAssignedLeadRcdSummaryByTier', {
            chart: {
                plotBackgroundColor: null,
                plotBorderWidth: null,
                plotShadow: false,
                type: 'pie'
            },
            title: {
                text: 'Total Leads Received Summary (by tier)',
                align: 'center'
            },
            tooltip: {
                pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
            },
            accessibility: {
                point: {
                    valueSuffix: '%'
                }
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true,
                        format: '<b>{y} Leads'
                    }
                }
            },
            series: [{
                name: 'Leads',
                colorByPoint: true,
                data: cData
            }]
        });
    }

    function createUnAssignedLeadRcdSummaryByLeadSourceChart(data, labels)
   {
        var cData = [];
        for (let index = 0; index < data.length; index++) {
            cData.push({name: labels[index], y: parseFloat(data[index])});
        }
        Highcharts.chart('UnAssignedLeadRcdSummaryByLeadSource', {
            chart: {
                plotBackgroundColor: null,
                plotBorderWidth: null,
                plotShadow: false,
                type: 'pie'
            },
            title: {
                text: 'Unassigned Leads Received Summary (by LeadSource)',
                align: 'center'
            },
            tooltip: {
                pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
            },
            accessibility: {
                point: {
                    valueSuffix: '%'
                }
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true,
                        format: '<b>{y} Leads'
                    }
                }
            },
            series: [{
                name: 'Leads',
                colorByPoint: true,
                data: cData
            }]
        });
    }

    function createAdvisorConversionChart(data, labels)
    {
        var cData = [];
        for (let index = 0; index < data.length; index++) {
            cData.push([labels[index], parseFloat(data[index])]);
        }
        Highcharts.chart('advisorConversion', {
            chart: {
                type: 'column'
            },
            title: {
                text: 'Advisor Conversion Report'
            },
            subtitle: {
            //  text: 'Source: <a href="https://worldpopulationreview.com/world-cities" target="_blank">World Population Review</a>'
            },
            xAxis: {
                type: 'category',
                labels: {
                    rotation: -45,
                    style: {
                        fontSize: '13px',
                        fontFamily: 'Verdana, sans-serif'
                    }
                }
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Total Gross Conversion'
                }
            },
            legend: {
                enabled: false
            },
            tooltip: {
                pointFormat: '<b>{point.y:.1f}</b>'
            },
            plotOptions: {
                series: {
                    pointWidth: 40
                }
            },
            series: [{
                name: 'Population',
                data: cData,
                dataLabels: {
                    enabled: true,
                    rotation: -90,
                    color: '#FFFFFF',
                    align: 'right',
                    format: '{point.y:.1f}', // one decimal
                    y: 10, // 10 pixels down from the top
                    style: {
                        fontSize: '13px',
                        fontFamily: 'Verdana, sans-serif'
                    }
                }
            }]
        });
    }

    function createLeadAssignCountSummaryByAdvisorChart(data, labels)
    {
        var cData = [];
        for (let index = 0; index < data.length; index++) {
            cData.push([labels[index], parseFloat(data[index])]);
        }
        Highcharts.chart('leadAssignCountSummaryByAdvisor', {
            chart: {
                type: 'column'
            },
            title: {
                text: 'Lead Assign Count Summary Per Advisor'
            },
            subtitle: {
            //  text: 'Source: <a href="https://worldpopulationreview.com/world-cities" target="_blank">World Population Review</a>'
            },
            xAxis: {
                type: 'category',
                labels: {
                    rotation: -45,
                    style: {
                        fontSize: '13px',
                        fontFamily: 'Verdana, sans-serif'
                    }
                }
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Total Gross Conversion'
                }
            },
            legend: {
                enabled: false
            },
            tooltip: {
                pointFormat: '<b>{point.y:.1f}</b>'
            },
            plotOptions: {
                series: {
                    pointWidth: 40
                }
            },
            series: [{
                name: 'Population',
                data: cData,
                dataLabels: {
                    enabled: true,
                    rotation: -90,
                    color: '#FFFFFF',
                    align: 'right',
                    format: '{point.y:.1f}', // one decimal
                    y: 10, // 10 pixels down from the top
                    style: {
                        fontSize: '13px',
                        fontFamily: 'Verdana, sans-serif'
                    }
                }
            }]
        });
    }

    //leadAssignCountSummaryByAdvisor

</script>
@endsection
