<?php
use App\Enums\DatabaseColumnsString;
if($property == DatabaseColumnsString::MOBILE)
{
    $alternative_mobiles = getAdditionalInfo($model->modelType, $record->id);
    if($alternative_mobiles) { ?>
        {{$record->$property}}
        <?php 
        foreach($alternative_mobiles as $mobile) { ?>
            , {{$mobile->mobile_no}};
        <?php }
    }
    
}else if($property == DatabaseColumnsString::EMAIL)
{
    $alternative_mobiles = getAdditionalInfo($model->modelType, $record->id);
    if($alternative_mobiles) { ?>
        {{$record->$property}}
        <?php foreach($alternative_mobiles as $email) { ?>
            , {{$email->email_address}}
        <?php }
    }  
} else { ?>
    {{ $property==DatabaseColumnsString::CAR_VALUE ? number_format($record->$property, 2) : $record->$property }}
<?php } ?>