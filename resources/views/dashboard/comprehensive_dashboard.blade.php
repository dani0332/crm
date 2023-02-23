@extends('layouts.app_livewire')
@section('title','Comprehensive Dashboard')
@section('content')

@push('scripts')
<meta name="csrf-token" content="{{ csrf_token() }}" />
<script src="{{ asset('vendors/jquery/dist/jquery.min.js') }}"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" />
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    var comprehensiveDashboardStatChart = {};
    var comprehensiveDashboardStats = <?php echo json_encode($comprehensiveDashboardStats) ?>;

    function createComprehensiveConversionChart(comprehensiveDashboardStats) {
        var data = [];
        for (let index = 0; index < comprehensiveDashboardStats[0].length; index++) {
            data.push({
                name: comprehensiveDashboardStats[0][index],
                y: parseFloat(comprehensiveDashboardStats[1][index])
            })
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

            series: [{
                name: 'Net Conversion',
                colorByPoint: true,
                data: data
            }]
        });

    }
    $(function() {
        createComprehensiveConversionChart(comprehensiveDashboardStats);
        $('#tier-filter, #user-filter, #team-filter , #excludeManualFilter').on('change', function(e) {
            debugger;
            var tierFilterValue = $('#tier-filter').val();
            var userFilterValue = $('#user-filter').val();
            var teamFilterValue = $('#team-filter').val();
            if(e.target.id == 'team-filter') {
                $.ajax({
                    url: "/get-users-by-team",
                    type: "post",
                    data: {'team_filter' : teamFilterValue } ,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (users) {
                        if (users) {
                            $('#user-filter').empty();
                            users.forEach(user => {
                                $('#user-filter').append($('<option>', {
                                    value: user.id,
                                    text : user.name
                                }));
                            });
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.log(textStatus, errorThrown);
                    }
                });
            }
            var excludeFilterValue = $('#excludeManualFilter option:selected').val();
            comprehensiveDashboardStatChart.showLoading();
            $.ajax({
                url: "/get-comp-filter-stats",
                type: "post",
                data: { 'tier_filter' : tierFilterValue, 'team_filter' : teamFilterValue , 'userFilter' : userFilterValue, 'excludeFilter' : excludeFilterValue } ,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (result) {
                    if (result) {
                        var labels = (typeof result[0]) == 'string' ? JSON.parse(result[0]) : result[0];
                        var data = (typeof result[1]) == 'string' ? JSON.parse(result[1]) : result[1];
                        var numbers = [];
                        for (let index = 0; index < data.length; index++) {
                            numbers.push(parseFloat(data[index]));
                        }
                        if (labels.length > 0) {
                            comprehensiveDashboardStatChart.destroy();
                            createComprehensiveConversionChart([labels, numbers]);
                        } else {
                            comprehensiveDashboardStatChart.destroy();
                            createComprehensiveConversionChart([
                                [''],
                                [0]
                            ]);
                        }
                    }
                    comprehensiveDashboardStatChart.hideLoading();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    comprehensiveDashboardStatChart.hideLoading();
                    console.log(textStatus, errorThrown);
                }
            });
        });
    });

    const selectSettings = {
        plugins: ['remove_button', 'checkbox_options'],
        create: true,
        onItemAdd: function() {
            this.setTextboxValue('');
            this.refreshOptions();
        },
    }

    new TomSelect(["#user-filter"], selectSettings);
    new TomSelect(["#tier-filter"], selectSettings);
    new TomSelect(["#team-filter"], selectSettings);
</script>
@endpush

<div>
    <div class="flex gap-4 justify-end mb-4">
        <div>
        <label>Advisor Filter</label>
        <select multiple name="users[]" id="user-filter">
                <option value="">Select Users</option>
                @foreach ($carUsers as $carUser)
                <option value="{{$carUser->id}}"> {{ $carUser->name }} </option>
                @endforeach
            </select>
        </div>
        <div>
        <label>Tiers Filter</label>
            <select multiple name="tiers[]" id="tier-filter">
                <option value="">Select Tier</option>
                @foreach ($tiers as $tier)
                <option value="{{$tier->id}}"> {{ $tier->name }} </option>
                @endforeach
            </select>
        </div>
        <div>
        <label>Teams Filter</label>
            <select multiple name="teams[]" id="team-filter">
                <option value="">Select Team</option>
                @foreach ($teams as $team)
                <option @if($commonTeam == $team->id) selected="selected" @endif  value="{{$team->id}}">{{$team->name}}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div>
        <div id="comprehensiveConversion"></div>
    </div>
</div>

<style>
.ts-control {
    width: 210px !important;
    padding: 9px 9px !important;
    border-radius: 6px !important;
}
</style>
@endsection
