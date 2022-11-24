@extends('layouts.app_dashboard')
@section('title','TPL Dashboard')
@section('content')
<div class="">
    <div class="row">
        <div class="p-6 m-20 bg-white rounded shadow">
            <form action="/tpl-conversion-dashboard">
                <select name="test">
                    @foreach ($type as $t)
                        <option value="{{$t['id']}}" >{{ $t['code'] }}</option>
                    @endforeach
                </select>
                <button type="submit"> Button</button>
            </form>
            {!! $chart->container() !!}
        </div>
    </div>
</div>
@endsection
