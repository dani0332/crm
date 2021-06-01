@extends('layouts.app')
@section('title','Claim Status')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Claim Status Detail</h2>
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
                            {{ $claimsstatus->id }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Text 
                        </td>
                        <td>
                            {{ $claimsstatus->text }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Text Ar 
                        </td>
                        <td>
                            {{ $claimsstatus->text_ar }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Sort Order 
                        </td>
                        <td>
                            {{ $claimsstatus->sort_order }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Is Active
                        </td>
                        <td>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" {{ $claimsstatus->is_active ? 'checked' : '' }} class="flat" name='is_active'>
                                </label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        
                    <td >
                        <a href="{{ route('claimsstatus.edit', ['claimsstatus' => $claimsstatus->id]) }}" class='no-style-btn'><i class="fa fa-edit"></i> </a>
                    </td>
                    <td >
                        <form action="{{ route('claimsstatus.destroy', ['claimsstatus' => $claimsstatus->id]) }}" method="POST">  
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