<?php
?>
    <div class="drag-container">
        <ul class="drag-list">
        @foreach ($leadStatuses as $item)
            @if($teamName == quoteTypeCode::Travel || $teamName == quoteTypeCode::Home || $teamName == quoteTypeCode::Health )
                @if($item->text == quoteStatusCode::NEWLEAD || $item->text == quoteStatusCode::QUOTED || $item->text == quoteStatusCode::FOLLOWEDUP || $item->text == quoteStatusCode::NEGOTIATION || $item->text == quoteStatusCode::PAYMENTPENDING)
                
                <x-my-leads-visual
                    :item="$item"
                    :model="$teamName"
                />    

                @endif
            @endif

            @if($teamName == quoteTypeCode::Business)
                @if($item->text == quoteStatusCode::NEWLEAD || $item->text == quoteStatusCode::QUOTED || $item->text == quoteStatusCode::PAYMENTPENDING || $item->text == quoteStatusCode::QUALIFIED || $item->text == quoteStatusCode::APPLICATION_PENDING || $item->text == quoteStatusCode::MISSING_DOCUMENTS || $item->text == quoteStatusCode::PENDINGUW || $item->text == quoteStatusCode::POLICY_DOCUMENTS_PENDING)
                <x-my-leads-visual
                    :item="$item"
                    :model="$teamName"
                />  
                @endif
            @endif
            
            @if($teamName == quoteTypeCode::Life)
                @if($item->text == quoteStatusCode::NEWLEAD || $item->text == quoteStatusCode::QUOTED || $item->text == quoteStatusCode::FOLLOWEDUP || $item->text == quoteStatusCode::NEGOTIATION || $item->text == quoteStatusCode::TRANSACTIONAPPROVED)
                <x-my-leads-visual
                    :item="$item"
                    :model="$teamName"
                />  
                @endif
            @endif

            @if($teamName == quoteTypeCode::Health)
                @if($item->text == quoteStatusCode::APPLICATION_PENDING || $item->text == quoteStatusCode::PENDINGUW || $item->text == quoteStatusCode::POLICY_DOCUMENTS_PENDING || $item->text == quoteStatusCode::TRANSACTIONAPPROVED)
                <x-my-leads-visual
                    :item="$item"
                    :model="$teamName"
                />  
                @endif
            @endif
        @endforeach
        </ul>
    </div>