@extends('layouts.app')
@section('title','Rent a Car')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Rent a Car Detail</h2>
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
                            {{ $rentacar->id }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Text 
                        </td>
                        <td>
                            {{ $rentacar->text }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Text Ar 
                        </td>
                        <td>
                            {{ $rentacar->text_ar }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Sort Order 
                        </td>
                        <td>
                            {{ $rentacar->sort_order }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Is Active
                        </td>
                        <td>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" {{ $rentacar->is_active ? 'checked' : '' }} class="flat" name='is_active'>
                                </label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        
                    <td >
                        <a href="{{ route('rentacar.edit', ['rentacar' => $rentacar->id]) }}" class='no-style-btn'><i class="fa fa-edit"></i> </a>
                    </td>
                    <td >
                        <form action="{{ route('rentacar.destroy', ['rentacar' => $rentacar->id]) }}" method="POST">  
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