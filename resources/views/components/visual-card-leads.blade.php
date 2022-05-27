<li class="drag-column drag-column-New">
        <span class="drag-column-header">
            <div>
            <input class="search_status" type="text" placeholder="Search.." name="{{$item->id}}" onblur="searchTerm(this)">
            <h2>
            {{ $item->text ?? $item->name }}
            @php 
            $result = getDataAgainstStatus($model->modelType, $item->id);
            @endphp
            
            </h2>
            <span>
            Total Leads
                <span class="float-right">&nbsp; {{$result ? number_format($result['total_leads']) : 0}} </span>
            </span><br />
            <span>
            Total Premium
                <span class="float-right">&nbsp; {{$result ? number_format($result['total_premium']) : 0}} </span>
            </span>
            
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
                                <a target="_blank" href="/quotes/{{strtolower($model->modelType)}}/{{$lead->uuid}}"><i class="fa fa-pencil" aria-hidden="true"></i></a>
                            </span>
                            <div class="pad-5"></div>
                            <div class="lead-person"><i class="fa fa-user font-1" aria-hidden="true"></i> &nbsp;
                            {{$lead->first_name}} {{$lead->last_name}} 
                            </div>
                            <div class="pad-5"></div>
                            <div class="lead-person"><i class="fa fa-building font-1" aria-hidden="true"></i> &nbsp;
                            {{$lead->company_name}}
                            </div>
                            <div class="pad-5"></div>
                            <div class="lead-cost"><i class="fa fa-usd font-1"></i>&nbsp; {{number_format($lead->premium)}}
                            </div>
                        </div>
                    </li> 
                @endforeach
                @if($result["total_leads"] && $result["total_leads"] > 10)
                    <a href="#" onclick="(loadMore({{$item->id}}))" class="quotePlanModalPopup load_more_btn" id="load_more_btn{{$item->id}}">Load More</a></span>
                @endif
                @endif
        </ul>
        <div class="drag-column-footer"></div>
    </li>