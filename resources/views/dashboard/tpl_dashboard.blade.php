@extends('layouts.app_livewire')
@section('title','TPL Dashboard')
@section('content')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" ></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script type="text/javascript">

      var labels =  JSON.parse('<?php echo json_encode($labels); ?>');
      var users =  JSON.parse('<?php echo json_encode($data); ?>');
    debugger;
      const data = {
        labels: labels,
        datasets: [{
          label: 'My First dataset',
          backgroundColor: 'rgb(255, 99, 132)',
          borderColor: 'rgb(255, 99, 132)',
          data: users,
        }]
      };

      const config = {
        type: 'line',
        data: data,
        options: {}
      };

      const myChart = new Chart(
        document.getElementById('myChart'),
        config
      );

</script>
<canvas id="myChart" height="100px"></canvas>
{{-- <div class="">
    <div class="row">
        <div class="col-md-12" style="float: right; margin-right: 100px;">
            <form action="/tpl-conversion-dashboard">
                <select name="test" class="form-control">
                    @foreach ($type as $t)
                        <option value="{{$t['id']}}" >{{ $t['code'] }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-warning btn-sm"> Button</button>
            </form>
        </div>
        <div class="p-6 m-20 bg-white rounded shadow">
            {{-- {!! $chart->container() !!} --}}
        </div>
    </div>
</div> --}}
@endsection
