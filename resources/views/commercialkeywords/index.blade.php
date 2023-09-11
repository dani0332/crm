@extends('layouts.app')
@section('title', 'View Teams')
@section('content')
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Commercial Keywords</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ route('admin.commercial.keywords.create') }}" class="btn btn-warning btn-sm">Add new
                                Keyword</a></li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    @if (session()->has('success'))
                        <div class="alert alert-success">{{ session()->get('success') }}</div>
                    @endif
                    @if (session()->has('message'))
                        <div class="alert alert-warning">{{ session()->get('message') }}</div>
                    @endif
                    <form method="POST" id="search-keywords" class="form-horizontal form-label-left" role="form"
                        data-parsley-validate="" novalidate="" autocomplete="off">
                        <div class="item form-group">
                            <div class="col">
                                <label class="col-form-label col-md-2 col-sm-2" for="searchfield">Keyword Name</label>
                                <div class="col-md-6 col-sm-6">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="name" id="name"
                                            placeholder="Search here...">
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <ul class="nav navbar-right panel_toolbox">
                                    <li><input type="submit" class="btn btn-warning btn-sm"></li>
                                    <li><input type="reset" class="btn btn-warning btn-sm"></li>
                                </ul>
                            </div>
                        </div>
                    </form>
                    @if (session()->has('message'))
                        <div class="alert alert-danger">{{ session()->get('message') }}</div>
                    @endif
                    <table class="table table-striped jambo_table commercial-keywords-data-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>Id</th>
                                <th>Key</th>
                                <th>Name</th>
                                <th>Created At</th>
                                <th>Updated At</th>
                            </tr>
                        </thead>

                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
