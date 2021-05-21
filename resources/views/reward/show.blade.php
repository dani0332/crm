@extends('layouts.app')
@section('title','Reward Detail')
@section('content')
<div class="row">
    <div class="col-md-12 col-sm-12 ">
        <div class="x_panel">
            <div class="x_title">
                <h2>Reward Detail</h2>
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
                            {{ $reward->id }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Coupon Code 
                        </td>
                        <td>
                            {{ $reward->coupon_code }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                           Start Date
                        </td>
                        <td>
                            {{ $reward->start_date }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                           End Date
                        </td>
                        <td>
                            {{ $reward->end_date }}
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Discount
                        </td>
                        <td>
                            {{ $reward->Discount }}
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Is Flat Discount
                        </td>
                        <td>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" {{ $reward->is_flat_discount ? 'checked' : '' }} class="flat" name='is_flat_discount'>
                                </label>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Is Active
                        </td>
                        <td>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" {{ $reward->is_active ? 'checked' : '' }} class="flat" name='is_active'>
                                </label>
                            </div>
                        </td>
                    </tr>

                    <tr>    
                    <td >
                        <a href="{{ route('reward.edit', ['reward' => $reward->id]) }}" class='btn btn-info btn-sm'><i class="fa fa-edit"></i> </a>
                    </td>
                    <td >
                        <form action="{{ route('reward.destroy', ['reward' => $reward->id]) }}" method="POST">  
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