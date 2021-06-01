@extends('layouts.app')
@section('title','Car Repair Coverage')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Car Repair Coverage Detail</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <br />
                <table class="table table-striped">
                    <tr>
                        <td>
                            Id 
                        </td>
                        <td>
                            {{ $carrepaircoverage->id }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Text 
                        </td>
                        <td>
                            {{ $carrepaircoverage->text }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Text Ar 
                        </td>
                        <td>
                            {{ $carrepaircoverage->text_ar }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Sort Order 
                        </td>
                        <td>
                            {{ $carrepaircoverage->sort_order }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Is Active
                        </td>
                        <td>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" {{ $carrepaircoverage->is_active ? 'checked' : '' }} class="flat" name='is_active'>
                                </label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        
                    <td >
                        <a href="{{ route('carrepaircoverage.edit', ['carrepaircoverage' => $carrepaircoverage->id]) }}" class='no-style-btn'><i class="fa fa-edit"></i> </a>
                    </td>
                    <td >
                        <form action="{{ route('carrepaircoverage.destroy', ['carrepaircoverage' => $carrepaircoverage->id]) }}" method="POST">  
                            @csrf 
                            @method('DELETE')
                            <button type="submit" class="no-style-btn"><i class="fa fa-trash"></i></button>
                        </form>
                    </td>
                    </tr>
                </table >
                    
                </div>
            </div>
        </div>
    </div>
</div>
@endsection