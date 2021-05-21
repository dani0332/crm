@extends('layouts.app')
@section('title','Partner Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Partner Detail</h2>
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
                            {{ $partner->id }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Name 
                        </td>
                        <td>
                            {{ $partner->name }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Name Ar 
                        </td>
                        <td>
                            {{ $partner->name_ar }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Logo Image 
                        </td>
                        <td>
                            {{ $partner->logo_image }}
                        </td>
                    </tr>
                    <tr>
                        
                    <td >
                        <a href="{{ route('partner.edit', ['partner' => $partner->id]) }}" class='btn btn-info btn-sm'><i class="fa fa-edit"></i> </a>
                    </td>
                    <td >
                        <form action="{{ route('partner.destroy', ['partner' => $partner->id]) }}" method="POST">  
                            @csrf 
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></button>
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