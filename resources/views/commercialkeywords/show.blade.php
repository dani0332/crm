@extends('layouts.app')
@section('title', 'Keyword Details')
@section('content')
    {{-- @inject('teamService', 'App\Services\TeamService' ) --}}
    <div class="row">
        <div class="col-md-12 col-sm-12 admin-detail">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Commercial Keyword Detail</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ route('admin.commercial.keywords') }}" class="btn btn-warning btn-sm">Commercial
                                Keywords List</a></li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <br />
                    @if (session()->has('success'))
                        <div class="alert alert-success">{{ session()->get('success') }}</div>
                    @endif
                    @if (session()->has('message'))
                        <div class="alert alert-danger">{{ session()->get('message') }}</div>
                    @endif
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="first_name"><b> Keyword Name
                                </b></label>
                            <div class="col-md-6 col-sm-6 ">
                                <p class="label-align-center">{{ $commercialKeyword->name }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="last_name"><b> Keyword
                                    Key</b></label>
                            <div class="col-md-6 col-sm-6 ">
                                <p class="label-align-center">{{ $commercialKeyword->key }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="updated_at"><b> Updated At
                                </b></label>
                            <div class="col-md-6 col-sm-6 ">
                                <p class="label-align-center">{{ $commercialKeyword->updated_at }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="created_by"><b> Created by
                                </b></label>
                            <div class="col-md-6 col-sm-6 ">
                                <p class="label-align-center">{{ $commercialKeyword->created_at }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <a id="texta"
                                href="{{ route('admin.commercial.keywords.edit', ['commercialKeyword' => $commercialKeyword->id]) }}"
                                class='btn btn-warning btn-sm'>Edit</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
