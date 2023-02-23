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


    .highcharts-figure, .highcharts-data-table table {
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
<meta name="csrf-token" content="{{ csrf_token() }}" />
<div class="container" style="width:100% !important;">
    <div class="row" style="background-color: #f9fafb;padding:35px;box-shadow: 5px 5px 5px 1px rgb(0 0 0 / 10%);float: left;">
        <div  style="padding: 10px;">
            <div class="col-md-12">
                <div class="col-md-2" style="box-shadow: 5px 5px 5px 1px rgb(0 0 0 / 10%));padding: 10px;margin-top: 10px;margin-left: 10px;text-align: center;border-radius:5px;min-width:325px;float:left;margin-left:10px;background-color: white;color:black;border:1px solid black; min-height:50px;">
                    <b style="color: cornflowerblue;">TOTAL LEADS RCVD</b>
                    <div style="text-align: center;">
                        <b id="totalLeadsReceived">{{$totalLeadsReceived}}</b>
                    </div>
                </div>
                <div class="col-md-2" style="box-shadow: 5px 5px 5px 1px rgb(0 0 0 / 10%);padding: 10px;margin-top: 10px;margin-left: 10px;text-align: center;border-radius:5px;min-width:325px;float:left;margin-left:10px;background-color: white;color:black;border:1px solid black; min-height:50px;">
                    <b style="color: cornflowerblue;">TOTAL LEADS RCVD ECOM</b>
                    <div style="text-align: center;">
                        <b id="totalLeadsReceivedEcommerce">{{$totalLeadsReceivedEcommerce}}</b>
                    </div>
                </div>
                <div class="col-md-2" style="box-shadow: 5px 5px 5px 1px rgb(0 0 0 / 10%);padding: 10px;margin-top: 10px;margin-left: 10px;text-align: center;border-radius:5px;min-width:325px;float:left;margin-left:10px;background-color: white;color:black;border:1px solid black; min-height:50px;">
                    <b style="color: cornflowerblue;">TOTAL UNASSIGNED LEADS</b>
                    <div style="text-align: center;">
                        <b id="totalUnAssignedLeadsReceived">{{$totalUnAssignedLeadsReceived}}</b>
                    </div>
                </div>
                <div class="col-md-2" style="box-shadow: 5px 5px 5px 1px rgb(0 0 0 / 10%);padding: 10px;margin-top: 10px;margin-left: 10px;text-align: center;border-radius:5px;min-width:325px;float:left;margin-left:10px;background-color: white;color:black;border:1px solid black; min-height:50px;">
                    <b style="color: cornflowerblue;">TOTAL UNASSIGNED LEADS ECOM</b>
                    <div style="text-align: center;">
                        <b id="totalUnAssignedLeadsReceivedEcommerce">{{$totalUnAssignedLeadsReceivedEcommerce}}</b>
                    </div>
                </div>
                <div class="col-md-2" style="box-shadow: 5px 5px 5px 1px rgb(0 0 0 / 10%);padding: 10px;margin-top: 10px;margin-left: 10px;text-align: center;border-radius:5px;min-width:325px;float:left;margin-left:10px;background-color: white;color:black;border:1px solid black; min-height:50px;">
                    <b style="color: cornflowerblue;">TOTAL UNASSIGNED REVIVAL LEADS</b>
                    <div style="text-align: center;">
                        <b id="totalUnAssignedRevivalLeads">{{$totalUnAssignedRevivalLeads}}</b>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row" style="background-color: #f9fafb;padding:35px;box-shadow: 5px 5px 5px 1px rgb(0 0 0 / 10%);float: left;width:100%;">
        <div class="col-md-12">
            <h2 style="text-align: center;color: cornflowerblue;font-size: 25px;font-weight: 600;margin-top: -40px;">LEADS ASSIGNED AVERAGE</h2>
            @foreach ($teamWiseLeadsAssignedAverage as $item)
            <div class="col-md-1"
                style="background-color:white;margin-top:10px; min-width:250px;float:left; margin-left:10px; border-radius: 8px;border: 1px solid black;padding: 10px;text-align: center;color:black;">
                <b  style="color: cornflowerblue;">{{strtoupper($item['teamName'])}}</b>
                <div style="text-align: center;">
                    <b id={{str_replace(' ', '', $item['teamName'])}}>{{ $item['totalLeadsCount'] .' / '. $item['totalUsersUnderTeam']. ' = ' .
                        number_format((float)$item['totalUsersUnderTeam'] == 0 ? 0 : $item['totalLeadsCount'] / $item['totalUsersUnderTeam'], 2, '.', '')
                        }}</b>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    <div style="clear: both;"></div>
    <div class="row">
        <div style="float:right;margin-top:70px;position:relative;z-index:1;">
            <input type="text" id="reloadDailyStatsDate" name="reloadDailyStatsDate" class="form-control" style="width: 345px;text-align: center;border-radius: 5px;"  />
             <button id="reloadDailyStats" style="height: 45px;" class="btn btn-primary">Go</button>
        </div>
    </div>
    <div style="clear: both;"></div>
    <div class="row mt-12" style="margin-top:20px;">
        <div class="col-md-12">
            <div class="col-md-6 ml-10  col-md-offset-1" style="width:  50%; float:left;">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <h1 class="text-xl text-left mb-14 mt-12 text-[#308BCA]">

                        </h1>
                        <div id="LeadRcdSummaryByTier"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 ml-10 col-md-offset-1" style="width: 40%; float:right;margin-top: 45px;">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div id="UnAssignedLeadRcdSummaryByTier"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div style="clear: both;">
    </div>
    <div class="row mt-12">
        <div class="col-md-6 ml-10 col-md-offset-1" style="width: 50%; float:left;margin-left: 50px;">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div id="UnAssignedLeadRcdSummaryByLeadSource" style="margin-top: 60px;"></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 ml-10 col-md-offset-1" style="width:  40%; float:left;">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div id="AssignedLeadRcdSummaryByLeadSource" style="margin-top: 60px;"></div>
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
        <div class="col-md-3 relative flex justify-end">
            <select
                class="inline-flex w-full justify-center pr-10 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-100"
                id="advisor-filter" style="margin-left: 10px;width:350px;" multiple="multiple">
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
        <div class="col-md-3 relative flex justify-end">
            <select
                class="inline-flex w-full justify-center pr-10 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-100"
                id="team-filter" multiple="multiple" style="margin-left: 10px;width:350px;">
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
<script src="https://cdn.jsdelivr.net/npm/@easepick/bundle@1.2.0/dist/index.umd.min.js"></script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.min.css" />
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>


<script type="text/javascript" defer>
   var leadsCountByTier = <?php echo json_encode($leadsCountByTier)?>;
   var unAssignedLeadsByTier = <?php echo json_encode($unAssignedLeadsByTier)?>;
   var revivalLeadsCount = <?php echo json_encode($revivalLeadsCount)?>;
   var advisorConversionData  = <?php echo json_encode($advisorConversionData)?>;
   var advisorLeadsAssignedData = <?php echo json_encode($advisorLeadsAssignedData)?>;
   var assignedLeadsBySource = <?php echo json_encode($assignedLeadsBySource)?>;
   var leadRcdSummaryByTierPieChart =  revivalLeadsCountChart = assignedLeadsBySourceChart = advisorConversionChart = leadAssignCountByAdvisorChart = {};
   $(function(){
        setTimeout(function() {
            window.location.reload(1);
        }, 80000);
        const picker = new easepick.create({
            element: document.getElementById('reloadDailyStatsDate'),
            css: [
            'https://cdn.jsdelivr.net/npm/@easepick/bundle@1.2.0/dist/index.css',
            ],
            zIndex: 10,
            format: "DD-MM-YYYY",
            LockPlugin: {
                maxDays: 31
            },
            PresetPlugin: {
                position: "right"
            },
            RangePlugin: {
                delimiter: ","
            },
            plugins: [
                "RangePlugin",
                "LockPlugin",
                "PresetPlugin"
            ],
        });
        picker.setStartDate(new Date());
        picker.setEndDate(new Date());
        $('#reloadDailyStats').on('click', function(){
            var selectedDate = $('#reloadDailyStatsDate').val();
            $.get('/get-recent-daily-stats?range=' + selectedDate , function (data) {
               if (data) {
                 $('#totalLeadsReceived').text(data['totalLeadsReceived']);
                 $('#totalLeadsReceivedEcommerce').text(data['totalLeadsReceivedEcommerce']);
                 $('#totalUnAssignedLeadsReceived').text(data['totalUnAssignedLeadsReceived']);
                 $('#totalUnAssignedLeadsReceivedEcommerce').text(data['totalUnAssignedLeadsReceivedEcommerce']);
                 $('#totalUnAssignedRevivalLeads').text(data['totalUnAssignedRevivalLeads']);
                 for (let index = 0; index < data['teamWiseLeadsAssignedAverage'].length; index++) {
                    const teamName = data['teamWiseLeadsAssignedAverage'][index]['teamName'];
                    const totalLeadsCount = data['teamWiseLeadsAssignedAverage'][index]['totalLeadsCount'];
                    const totalUsersUnderTeam = data['teamWiseLeadsAssignedAverage'][index]['totalUsersUnderTeam'];
                    $('#'+ teamName.replace(/ /g,'')).text(totalLeadsCount + ' / ' + totalUsersUnderTeam + ' = ' + totalLeadsCount/totalUsersUnderTeam);
                 }
                 leadRcdSummaryByTierPieChart.destroy();
                 var leadRcdSummaryByTierPieChartData = [];
                 for (let index = 0; index < data['leadsCountByTier'].length; index++) {
                    const name = data['leadsCountByTier'][index]['tierNames'];
                    const count = data['leadsCountByTier'][index]['leadCount'];
                    leadRcdSummaryByTierPieChartData.push({ name: name, y: count });
                 }
                 createLeadRcdSummaryByTierPieChart(leadRcdSummaryByTierPieChartData);

                 revivalLeadsCountChart.destroy();
                 var revivalLeadsCountChartData = [{name : 'Revival Leads', y: parseInt(data['revivalLeadsCount'][0]['revival_leads']) },
                    {name : 'Non Revival Leads', y: parseInt(data['revivalLeadsCount'][0]['non_revival_leads']) }];
                 createUnAssignedLeadRcdSummaryByLeadSourceChart(revivalLeadsCountChartData);
               }
            });
        });
        $('#advisor-filter').on('change', function (e) {
            var advisorFitlerValue = $('#advisor-filter').val();
            advisorConversionChart.showLoading();
            $.ajax({
                url: "/get-advisor-conversion-stats",
                type: "post",
                data: { 'advisorFilter' : advisorFitlerValue} ,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (result) {
                    if(result) {
                        var labels = (typeof result[0]) == 'string' ? JSON.parse(result[0]) : result[0];
                        var data = (typeof result[1]) == 'string' ? JSON.parse(result[1]) : result[1];
                        var numbers = [];
                        var cData = [];
                        for (let index = 0; index < result.length; index++) {
                            var node = result[index];
                            cData.push({name: node.name + '-(' + node.start_date + ' to ' + node.end_date +  ')', y: parseFloat( ( node.sale_leads - node.created_sale_leads ) / (node.total_leads - node.bad_leads - node.manual_created )  )});
                        }
                        if(cData.length > 0 ){
                            advisorConversionChart.destroy();
                            createAdvisorConversionChart(cData);
                        }else{
                            advisorConversionChart.destroy();
                            createAdvisorConversionChart([{name: '', y: 0}]);
                        }
                    }
                    advisorConversionChart.hideLoading();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    advisorConversionChart.hideLoading();
                    console.log(textStatus, errorThrown);
                }
            });
        });
        $('#team-filter').on('change', function (e) {
            var teamFilteValue = $('#team-filter').val();
            leadAssignCountByAdvisorChart.showLoading();
            $.ajax({
                url: "/get-team-conversion-stats",
                type: "post",
                data: { 'teamFilter' : teamFilteValue} ,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (result) {
                    if(result) {
                        var cData = [];
                        for (let index = 0; index < result.length; index++) {
                            var node = result[index];
                            cData.push({name: node.name, y: parseFloat( node.total_leads )});
                        }
                        if(cData.length > 0 ){
                            leadAssignCountByAdvisorChart.destroy();
                            createLeadAssignCountSummaryByAdvisorChart(cData);
                        }else{
                            leadAssignCountByAdvisorChart.destroy();
                            createLeadAssignCountSummaryByAdvisorChart([{name: '', y: 0}]);
                        }
                    }
                    leadAssignCountByAdvisorChart.hideLoading();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    leadAssignCountByAdvisorChart.hideLoading();
                    console.log(textStatus, errorThrown);
                }
            });
        });
        var cData = [];
        for (let index = 0; index < leadsCountByTier.length; index++) {
            cData.push({name: leadsCountByTier[index]['tierNames'], y: parseFloat(leadsCountByTier[index]['leadCount'])});
        }
        createLeadRcdSummaryByTierPieChart(cData);


        var cData = [];
        for (let index = 0; index < unAssignedLeadsByTier.length; index++) {
            cData.push({name: unAssignedLeadsByTier[index]['tierNames'], y: parseFloat(unAssignedLeadsByTier[index]['leadCount'])});
        }
        createUnAssignedLeadRcdSummaryByTierChart(cData);


        var cData = [{name : 'Revival Leads', y: parseInt(revivalLeadsCount[0]['revival_leads']) },
        {name : 'Non Revival Leads', y: parseInt(revivalLeadsCount[0]['non_revival_leads']) }];
        createUnAssignedLeadRcdSummaryByLeadSourceChart(cData);



        var cData = [];
        for (let index = 0; index < assignedLeadsBySource.length; index++) {
            cData.push({name: assignedLeadsBySource[index]['sourceName'], y: parseFloat(assignedLeadsBySource[index]['sourceCount'])});
        }
        createAssignedLeadRcdSummaryByLeadSourceChart(cData);


        var cData = [];
        for (let index = 0; index < advisorConversionData.length; index++) {
            var node = advisorConversionData[index];
            cData.push({name: node.name + '-(' + node.start_date + ' to ' + node.end_date +  ')', y: parseFloat(  ( node.sale_leads - node.created_sale_leads ) / (node.total_leads - node.bad_leads - node.manual_created ) )});
        }
        createAdvisorConversionChart(cData);


        var cData = [];
        for (let index = 0; index < advisorLeadsAssignedData.length; index++) {
            var node = advisorLeadsAssignedData[index];
            cData.push({name: node['name'], y: parseFloat( node['total_leads'] )});
        }
        createLeadAssignCountSummaryByAdvisorChart(cData);

   });
   function createLeadRcdSummaryByTierPieChart(data)
   {

        leadRcdSummaryByTierPieChart = Highcharts.chart('LeadRcdSummaryByTier', {
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
                        format: 'Tier {point.name} - <b>{y} Leads'
                    }
                }
            },
            series: [{
                name: 'Leads',
                colorByPoint: true,
                data: data
            }]
        });

   }

   function createUnAssignedLeadRcdSummaryByTierChart(data)
   {
        unAssignedLeadsByTierChart = Highcharts.chart('UnAssignedLeadRcdSummaryByTier', {
            chart: {
                plotBackgroundColor: null,
                plotBorderWidth: null,
                plotShadow: false,
                type: 'pie'
            },
            title: {
                text: 'Unassigned Leads Received Summary (by tier)',
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
                        format: 'Tier {point.name} - <b>{y} Leads'
                    }
                }
            },
            series: [{
                name: 'Leads',
                colorByPoint: true,
                data: data
            }]
        });
    }

    function createUnAssignedLeadRcdSummaryByLeadSourceChart(data)
   {
        revivalLeadsCountChart = Highcharts.chart('UnAssignedLeadRcdSummaryByLeadSource', {
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
                        format: '{point.name} - <b>{y} Leads'
                    }
                }
            },
            series: [{
                name: 'Leads',
                colorByPoint: true,
                data: data
            }]
        });
    }

    function createAssignedLeadRcdSummaryByLeadSourceChart(data)
   {
    assignedLeadsBySourceChart = Highcharts.chart('AssignedLeadRcdSummaryByLeadSource', {
            chart: {
                plotBackgroundColor: null,
                plotBorderWidth: null,
                plotShadow: false,
                type: 'pie'
            },
            title: {
                text: 'Total Leads Received Summary (by LeadSource)',
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
                        format: '{point.name} - <b>{y} Leads'
                    }
                }
            },
            series: [{
                name: 'Leads',
                colorByPoint: true,
                data: data
            }]
        });
    }

    function createAdvisorConversionChart(data)
    {

        advisorConversionChart =  Highcharts.chart('advisorConversion', {
            chart: {
                type: 'column'
            },
            title: {
                align: 'center',
                text: 'ADVISOR CONVERSION REPORT'
            },
            xAxis: {
                type: 'category'
            },
            yAxis: {
                title: {
                    text: 'Total Gross Conversion'
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
                    name: 'Gross Conversion',
                    colorByPoint: true,
                    data: data
                }
            ]
        });

    }

    function createLeadAssignCountSummaryByAdvisorChart(data)
    {

        leadAssignCountByAdvisorChart = Highcharts.chart('leadAssignCountSummaryByAdvisor', {
            chart: {
                type: 'column'
            },
            title: {
                align: 'center',
                text: 'Lead Assign Count Summary Per Advisor'
            },
            xAxis: {
                type: 'category'
            },
            yAxis: {
                title: {
                    text: 'Number of Leads Assigned'
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
                        format: '{point.y:.1f}'
                    }
                }
            },
            tooltip: {
                headerFormat: '<span style="font-size:11px">{series.name}</span><br>',
                pointFormat: '<span style="color:{point.color}">{point.name}</span>: <b>{point.y:.2f}</b>'
            },
            series: [
                {
                    name: 'Leads Assigned',
                    colorByPoint: true,
                    data: data
                }
            ]
        });
    }
    function createColumnChart(dataSource, titleText, leftTitle, toolTipHeading, chartVariable)
    {

    }

    const selectSettings = {
        plugins: ['remove_button', 'checkbox_options'],
        create: true,
        onItemAdd: function() {
            this.setTextboxValue('');
            this.refreshOptions();
        },
    }

    new TomSelect(["#team-filter"], selectSettings);
    new TomSelect(["#advisor-filter"], selectSettings);
</script>
@endsection
