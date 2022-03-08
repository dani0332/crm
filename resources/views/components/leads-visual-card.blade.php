<div class="drag-container">
        <ul class="drag-list">
        @foreach ($model->properties as $property => $value)
            @if (strpos($value, 'select') !== false && $property == "quote_status_id")
                @foreach ($dropdownSource[$property] as $item)
                    @if(strtolower($model->modelType) == 'travel' || strtolower($model->modelType) == 'home' || strtolower($model->modelType) == 'health' )
                        @if($item->text == "New Lead" || $item->text == "Quoted" || $item->text == "Followed Up" || $item->text == "In Negotiation" || $item->text == "Payment Pending")
                            <li class="drag-column drag-column-New">
                                <span class="drag-column-header">
                                    <div>
                                    <input class="search_status" type="text" placeholder="Search.." name="{{$item->id}}" onblur="searchTerm(this)">
                                    <h2>
                                    {{ $item->text ?? $item->name }}
                                    @php 
                                    $result = getDataAgainstStatus($model->modelType, $item->id);
                                    @endphp
                                        <!-- <span class="float-right"> : &nbsp; {{$result ? $result['total_premium'] : 0}} </span> -->
                                    </h2>
                                    <h4>
                                    Total Leads
                                        <span class="float-right">&nbsp; {{$result ? $result['total_leads'] : 0}} </span>
                                    </h4>
                                    <h4>
                                    Total Premium
                                        <span class="float-right">&nbsp; {{$result ? $result['total_premium'] : 0}} </span>
                                    </h4>
                                    <!-- <a href="https://demo.krayincrm.com/krayincrm-39-53-83-92/admin/leads/create?stage_id=1">
                                    Create Lead
                                    </a> -->
                                    </div>
                                </span>
                                <div class="drag-options"></div>
                                <ul data-status="New" class="drag-inner-list status_list{{$item->id}}" id="">
                                    @if(!empty($result['leads_list']))
                                        @foreach($result['leads_list'] as $lead)
                                            <li class="drag-item">
                                                <div class="lead-block rotten">
                                                    <div class="lead-title">{{$lead->code}}</div>
                                                    <span class="float-right">
                                                        <a href="#" planDetailUrl="{{ $lead->id }}/lead_details?modelType={{$model->modelType}}" data-toggle="modal" data-target="#quoteModal" class="quotePlanModalPopup"><i class="fa fa-pencil" aria-hidden="true"></i></a>
                                                    </span>
                                                    <div class="pad-5"></div>
                                                    <div class="lead-person"><i class="fa fa-user font-1" aria-hidden="true"></i>
                                                    {{$lead->first_name}} {{$lead->last_name}} 
                                                    </div>
                                                    <div class="pad-5"></div>
                                                    <div class="lead-cost"><i class="fa fa-usd font-1"></i>&nbsp;{{$lead->premium}}
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                        @if($result["total_leads"])
                                            <a href="#" onclick="(loadMore({{$item->id}}))" class="quotePlanModalPopup load_more_btn" id="load_more_btn{{$item->id}}">Load More</a></span>
                                        @endif
                                        @endif
                                </ul>
                                <div class="drag-column-footer"></div>
                            </li>
                        @endif
                    @endif

                    @if(strtolower($model->modelType) == 'business')
                        @if($item->text == "New Lead" || $item->text == "Quoted" || $item->text == "Payment Pending" || $item->text == "Qualified" || $item->text == "Application Pending" || $item->text == "Missing Documents Requested"|| $item->text == "Pending with UW"|| $item->text == "Policy Documents Pending")
                            <li class="drag-column drag-column-New">
                                <span class="drag-column-header">
                                    <div>
                                    <input class="search_status" type="text" placeholder="Search.." name="{{$item->id}}" onblur="searchTerm(this)">
                                    <h2>
                                    {{ $item->text ?? $item->name }}
                                    @php 
                                    $result = getDataAgainstStatus($model->modelType, $item->id);
                                    @endphp
                                        <!-- <span class="float-right"> : &nbsp; {{$result ? $result['total_premium'] : 0}} </span> -->
                                    </h2>
                                    <h4>
                                    Total Leads
                                        <span class="float-right">&nbsp; {{$result ? $result['total_leads'] : 0}} </span>
                                    </h4>
                                    <h4>
                                    Total Premium
                                        <span class="float-right">&nbsp; {{$result ? $result['total_premium'] : 0}} </span>
                                    </h4>
                                    <!-- <a href="https://demo.krayincrm.com/krayincrm-39-53-83-92/admin/leads/create?stage_id=1">
                                    Create Lead
                                    </a> -->
                                    </div>
                                </span>
                                <div class="drag-options"></div>
                                <ul data-status="New" class="drag-inner-list status_list{{$item->id}}" id="">
                                    @if(!empty($result['leads_list']))
                                        @foreach($result['leads_list'] as $lead)
                                            <li class="drag-item">
                                                <div class="lead-block rotten">
                                                    <div class="lead-title">{{$lead->code}}</div>
                                                    <span class="float-right">
                                                        <a href="#" planDetailUrl="{{ $lead->id }}/lead_details?modelType={{$model->modelType}}" data-toggle="modal" data-target="#quoteModal" class="quotePlanModalPopup"><i class="fa fa-pencil" aria-hidden="true"></i></a>
                                                    </span>
                                                    <div class="pad-5"></div>
                                                    <div class="lead-person"><i class="fa fa-user font-1" aria-hidden="true"></i>
                                                    {{$lead->first_name}} {{$lead->last_name}} 
                                                    </div>
                                                    <div class="pad-5"></div>
                                                    <div class="lead-cost"><i class="fa fa-usd font-1"></i>&nbsp;{{$lead->premium}}
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                        @if($result["total_leads"])
                                            <a href="#" onclick="(loadMore({{$item->id}}))" class="quotePlanModalPopup load_more_btn" id="load_more_btn{{$item->id}}">Load More</a></span>
                                        @endif
                                        @endif
                                </ul>
                                <div class="drag-column-footer"></div>
                            </li>
                        @endif
                    @endif
                    
                    @if(strtolower($model->modelType) == 'life')
                        @if($item->text == "New Lead" || $item->text == "Quoted" || $item->text == "Followed Up" || $item->text == "In Negotiation" || $item->text == "Transaction Approved")
                            <li class="drag-column drag-column-New">
                                <span class="drag-column-header">
                                    <div>
                                    <input class="search_status" type="text" placeholder="Search.." name="{{$item->id}}" onblur="searchTerm(this)">
                                    <h2>
                                    {{ $item->text ?? $item->name }}
                                    @php 
                                    $result = getDataAgainstStatus($model->modelType, $item->id);
                                    @endphp
                                        <!-- <span class="float-right"> : &nbsp; {{$result ? $result['total_premium'] : 0}} </span> -->
                                    </h2>
                                    <h4>
                                    Total Leads
                                        <span class="float-right">&nbsp; {{$result ? $result['total_leads'] : 0}} </span>
                                    </h4>
                                    <h4>
                                    Total Premium
                                        <span class="float-right">&nbsp; {{$result ? $result['total_premium'] : 0}} </span>
                                    </h4>
                                    <!-- <a href="https://demo.krayincrm.com/krayincrm-39-53-83-92/admin/leads/create?stage_id=1">
                                    Create Lead
                                    </a> -->
                                    </div>
                                </span>
                                <div class="drag-options"></div>
                                <ul data-status="New" class="drag-inner-list status_list{{$item->id}}" id="">
                                    @if(!empty($result['leads_list']))
                                        @foreach($result['leads_list'] as $lead)
                                            <li class="drag-item">
                                                <div class="lead-block rotten">
                                                    <div class="lead-title">{{$lead->code}}</div>
                                                    <span class="float-right">
                                                        <a href="#" planDetailUrl="{{ $lead->id }}/lead_details?modelType={{$model->modelType}}" data-toggle="modal" data-target="#quoteModal" class="quotePlanModalPopup"><i class="fa fa-pencil" aria-hidden="true"></i></a>
                                                    </span>
                                                    <div class="pad-5"></div>
                                                    <div class="lead-person"><i class="fa fa-user font-1" aria-hidden="true"></i>
                                                    {{$lead->first_name}} {{$lead->last_name}} 
                                                    </div>
                                                    <div class="pad-5"></div>
                                                    <div class="lead-cost"><i class="fa fa-usd font-1"></i>&nbsp;{{$lead->premium}}
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                        @if($result["total_leads"])
                                            <a href="#" onclick="(loadMore({{$item->id}}))" class="quotePlanModalPopup load_more_btn" id="load_more_btn{{$item->id}}">Load More</a></span>
                                        @endif
                                        @endif
                                </ul>
                                <div class="drag-column-footer"></div>
                            </li>
                        @endif
                    @endif

                    @if(strtolower($model->modelType) == 'health')
                        @if($item->text == "For Application" || $item->text == "Pending with UW" || $item->text == "Pending Policy Docs" || $item->text == "Transaction Approved")
                            <li class="drag-column drag-column-New">
                                <span class="drag-column-header">
                                    <div>
                                    <input class="search_status" type="text" placeholder="Search.." name="{{$item->id}}" onblur="searchTerm(this)">
                                    <h2>
                                    {{ $item->text ?? $item->name }}
                                    @php 
                                    $result = getDataAgainstStatus($model->modelType, $item->id);
                                    @endphp
                                        <!-- <span class="float-right"> : &nbsp; {{$result ? $result['total_premium'] : 0}} </span> -->
                                    </h2>
                                    <h4>
                                    Total Leads
                                        <span class="float-right">&nbsp; {{$result ? $result['total_leads'] : 0}} </span>
                                    </h4>
                                    <h4>
                                    Total Premium
                                        <span class="float-right">&nbsp; {{$result ? $result['total_premium'] : 0}} </span>
                                    </h4>
                                    <!-- <a href="https://demo.krayincrm.com/krayincrm-39-53-83-92/admin/leads/create?stage_id=1">
                                    Create Lead
                                    </a> -->
                                    </div>
                                </span>
                                <div class="drag-options"></div>
                                <ul data-status="New" class="drag-inner-list status_list{{$item->id}}" id="">
                                    @if(!empty($result['leads_list']))
                                        @foreach($result['leads_list'] as $lead)
                                            <li class="drag-item">
                                                <div class="lead-block rotten">
                                                    <div class="lead-title">{{$lead->code}}</div>
                                                    <span class="float-right">
                                                        <a href="#" planDetailUrl="{{ $lead->id }}/lead_details?modelType={{$model->modelType}}" data-toggle="modal" data-target="#quoteModal" class="quotePlanModalPopup"><i class="fa fa-pencil" aria-hidden="true"></i></a>
                                                    </span>
                                                    <div class="pad-5"></div>
                                                    <div class="lead-person"><i class="fa fa-user font-1" aria-hidden="true"></i>
                                                    {{$lead->first_name}} {{$lead->last_name}} 
                                                    </div>
                                                    <div class="pad-5"></div>
                                                    <div class="lead-cost"><i class="fa fa-usd font-1"></i>&nbsp;{{$lead->premium}}
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                        @if($result["total_leads"])
                                            <a href="#" onclick="(loadMore({{$item->id}}))" class="quotePlanModalPopup load_more_btn" id="load_more_btn{{$item->id}}">Load More</a></span>
                                        @endif
                                        @endif
                                </ul>
                                <div class="drag-column-footer"></div>
                            </li>
                        @endif
                    @endif

                @endforeach
            @endif
        @endforeach
        </ul>
    </div>