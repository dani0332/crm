<?php use App\Enums\LeadsQuoteStatus; ?>
<div class="drag-container">
        <ul class="drag-list">
        @foreach ($model->properties as $property => $value)
            @if (strpos($value, 'select') !== false && $property == "quote_status_id")
                @foreach ($dropdownSource[$property] as $item)
                    @if(strtolower($model->modelType) == 'travel' || strtolower($model->modelType) == 'home' || strtolower($model->modelType) == 'health' )
                        @if($item->text == LeadsQuoteStatus::NEWLEAD || $item->text == LeadsQuoteStatus::QUOTED || $item->text == LeadsQuoteStatus::FOLLOWEDUP || $item->text == LeadsQuoteStatus::NEGOTIATION || $item->text == LeadsQuoteStatus::PAYMENTPENDING)
                        <x-visual-card-leads
                            :item="$item"
                            :model="$model"
                        />    

                        @endif
                    @endif

                    @if(strtolower($model->modelType) == 'business')
                        @if($item->text == LeadsQuoteStatus::NEWLEAD || $item->text == LeadsQuoteStatus::QUOTED || $item->text == LeadsQuoteStatus::PAYMENTPENDING || $item->text == LeadsQuoteStatus::QUALIFIED || $item->text == LeadsQuoteStatus::APPLICATION_PENDING || $item->text == LeadsQuoteStatus::MISSING_DOCUMENTS || $item->text == LeadsQuoteStatus::PENDINGUW || $item->text == LeadsQuoteStatus::PLOICY_DOCUMENTS_PENDING)
                        <x-visual-card-leads
                            :item="$item"
                            :model="$model"
                        />  
                        @endif
                    @endif
                    
                    @if(strtolower($model->modelType) == 'life')
                        @if($item->text == LeadsQuoteStatus::NEWLEAD || $item->text == LeadsQuoteStatus::QUOTED || $item->text == LeadsQuoteStatus::FOLLOWEDUP || $item->text == LeadsQuoteStatus::NEGOTIATION || $item->text == LeadsQuoteStatus::TRANSACTION_APPROVED)
                        <x-visual-card-leads
                            :item="$item"
                            :model="$model"
                        />  
                        @endif
                    @endif

                    @if(strtolower($model->modelType) == 'health')
                        @if($item->text == LeadsQuoteStatus::APPLICATION_PENDING || $item->text == LeadsQuoteStatus::PENDINGUW || $item->text == LeadsQuoteStatus::PLOICY_DOCUMENTS_PENDING || $item->text == LeadsQuoteStatus::TRANSACTION_APPROVED)
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