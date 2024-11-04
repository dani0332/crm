<?php
?>
<div class="drag-container">
        <ul class="drag-list">
        @foreach ($model->properties as $property => $value)
            @if (strpos($value, 'select') !== false && $property == "quote_status_id")
                @foreach ($dropdownSource[$property] as $item)
                    @if($model->modelType == quoteTypeCode::Health && Auth::user()->isHealthWCUAdvisor())
                    @if($item->text == quoteStatusCode::NEWLEAD || $item->text == quoteStatusCode::QUALIFIED || $item->text == quoteStatusCode::QualificationPending)

                        <x-visual-card-leads
                            :item="$item"
                            :model="$model"
                        />

                        @endif

                    @endif
                    @if($model->modelType == quoteTypeCode::Travel || $model->modelType == quoteTypeCode::Home || ($model->modelType == quoteTypeCode::Health &&  !Auth::user()->isHealthWCUAdvisor()) )
                        @if($item->text == quoteStatusCode::NEWLEAD || $item->text == quoteStatusCode::QUOTED || $item->text == quoteStatusCode::FOLLOWEDUP || $item->text == quoteStatusCode::NEGOTIATION || $item->text == quoteStatusCode::PAYMENTPENDING)

                        <x-visual-card-leads
                            :item="$item"
                            :model="$model"
                        />

                        @endif
                    @endif

                    @if($model->modelType == quoteTypeCode::Business)
                        @if($item->text == quoteStatusCode::NEWLEAD || $item->text == quoteStatusCode::QUOTED || $item->text == quoteStatusCode::PAYMENTPENDING || $item->text == quoteStatusCode::QUALIFIED || $item->text == quoteStatusCode::APPLICATION_PENDING || $item->text == quoteStatusCode::MISSING_DOCUMENTS || $item->text == quoteStatusCode::PENDINGUW || $item->text == quoteStatusCode::POLICY_DOCUMENTS_PENDING)
                        <x-visual-card-leads
                            :item="$item"
                            :model="$model"
                        />
                        @endif
                    @endif

                    @if($model->modelType == quoteTypeCode::Life)
                        @if($item->text == quoteStatusCode::NEWLEAD || $item->text == quoteStatusCode::QUOTED || $item->text == quoteStatusCode::FOLLOWEDUP || $item->text == quoteStatusCode::NEGOTIATION || $item->text == quoteStatusCode::FOLLOWEDUP)
                        <x-visual-card-leads
                            :item="$item"
                            :model="$model"
                        />
                        @endif
                    @endif

                    @if($model->modelType == quoteTypeCode::Health && !Auth::user()->isHealthWCUAdvisor())
                        @if($item->text == quoteStatusCode::APPLICATION_PENDING || $item->text == quoteStatusCode::PENDINGUW || $item->text == quoteStatusCode::POLICY_DOCUMENTS_PENDING || $item->text == quoteStatusCode::TRANSACTIONAPPROVED || $item->text == quoteStatusCode::FOLLOWEDUP || $item->text == quoteStatusCode::POLICY_DOCUMENTS_PENDING)
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
