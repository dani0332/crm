@extends('layouts.app')
@section('title', 'Activity Detail')
@section('content')
    <div class="row">
        <div class="col-md-12 col-sm-12 admin-detail">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Activity Detail</h2>
                    <ul class="nav navbar-right panel_toolbox">
                        <li><a href="{{ route('activities.index') }}" class="btn btn-warning btn-sm">Activity List</a></li>
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
                            <label class="col-form-label col-md-3 col-sm-3 label-align"
                                for="first_name"><b>Title</b></label>
                            <div class="col-md-6 col-sm-6 ">
                                <p class="label-align-center">{{ $record->title }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="last_name"><b> Client Name
                                </b></label>
                            <div class="col-md-6 col-sm-6 ">
                                <p class="label-align-center">{{ $record->client_name }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="email_address"><b> Quote
                                    Request Id </b></label>
                            <div class="col-md-6 col-sm-6 ">
                                <p class="label-align-center">
                                    @php
                                        $quotetypename = '';
                                        switch ($record->quote_type_id) {
                                            case 1:
                                                $quotetypename = 'car';
                                                break;
                                            case 2:
                                                $quotetypename = 'home';
                                                break;
                                            case 3:
                                                $quotetypename = 'health';
                                                break;
                                            case 4:
                                                $quotetypename = 'life';
                                                break;
                                            case 5:
                                                $quotetypename = 'business';
                                                break;
                                            case 6:
                                                $quotetypename = 'bike';
                                                break;
                                            case 7:
                                                $quotetypename = 'yacht';
                                                break;
                                            case 8:
                                                $quotetypename = 'travel';
                                                break;
                                        }
                                        $url = '/quotes/' . strtolower($quotetypename) . '/' . $record->quote_uuid;
                                    @endphp
                                    <a target="_blank" href="{{ $url }}">{{ strtoupper($record->quote_uuid) }}</a>
                                </p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="phone_number"><b>
                                    Due Date </b></label>
                            <div class="col-md-6 col-sm-6 ">
                                <p class="label-align-center">{{ $record->due_date }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="agency"><b> Assignee
                                </b></label>
                            <div class="col-md-6 col-sm-6 ">
                                <p class="label-align-center">{{ $record->assignee_name }}</p>
                            </div>
                        </div>
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="agency"><b> Quote Request Type
                                </b></label>
                            <div class="col-md-6 col-sm-6 ">
                                <p class="label-align-center">{{ strtoupper($quotetypename) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="item form-group">
                        <div class="col">
                            <label class="col-form-label col-md-3 col-sm-3 label-align" for="agency"><b> Description
                                </b></label>
                            <div class="col-md-6 col-sm-6 ">
                                <p class="label-align-center">{{ $record->description }}</p>
                            </div>
                        </div>
                        <div class="col">
                        </div>
                    </div>
                    <div class="ln_solid"></div>
                    <div class="row">
                        <div class="col-auto mr-auto"></div>
                        <div class="col-auto">
                            <a id="texta" href="{{ route('activities.edit', $record->uuid) }}"
                                class='btn btn-warning btn-sm'>Edit</a>
                            <a href="#" date-route="{{ route('activities.destroy', $record->uuid) }}"
                                class='btn btn-warning btn-sm delete'>Delete</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
