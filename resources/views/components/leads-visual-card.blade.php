<div class="drag-container">
        <ul class="drag-list">
        @foreach ($model->properties as $property => $value)
            @if (strpos($value, 'select') !== false && $property == "quote_status_id")
                @foreach ($dropdownSource[$property] as $item)
                    @if(strtolower($model->modelType) == 'travel' || strtolower($model->modelType) == 'home' || strtolower($model->modelType) == 'health' )
                        @if($item->text == "New Lead" || $item->text == "Quoted" || $item->text == "Followed Up" || $item->text == "In Negotiation" || $item->text == "Payment Pending")
                        <x-visual-card-leads
                            :item="$item"
                            :model="$model"
                        />    

                        @endif
                    @endif

                    @if(strtolower($model->modelType) == 'business')
                        @if($item->text == "New Lead" || $item->text == "Quoted" || $item->text == "Payment Pending" || $item->text == "Qualified" || $item->text == "Application Pending" || $item->text == "Missing Documents Requested"|| $item->text == "Pending with UW"|| $item->text == "Policy Documents Pending")
                        <x-visual-card-leads
                            :item="$item"
                            :model="$model"
                        />  
                        @endif
                    @endif
                    
                    @if(strtolower($model->modelType) == 'life')
                        @if($item->text == "New Lead" || $item->text == "Quoted" || $item->text == "Followed Up" || $item->text == "In Negotiation" || $item->text == "Transaction Approved")
                        <x-visual-card-leads
                            :item="$item"
                            :model="$model"
                        />  
                        @endif
                    @endif

                    @if(strtolower($model->modelType) == 'health')
                        @if($item->text == "Application Pending" || $item->text == "Pending with UW" || $item->text == "Policy Documents Pending" || $item->text == "Transaction Approved")
                        <x-visual-card-leads
                            :item="$item"
                            :model="$model"
                        />  
                        @endif
                    @endif

                @endforeach
            @endif
        @endforeach
        </ul>
    </div>