<?php
use App\Enums\DatabaseColumnsString;
if($property == DatabaseColumnsString::quotemobile)
{
    $alternative_mobiles = getAdditionalInfo($model->modelType, $record->id);
    if($alternative_mobiles) { ?>
        {{$record->$property}}
        <?php 
        foreach($alternative_mobiles as $mobile) { ?>
            ", " {{$mobile->mobile_no}};
        <?php }
    }
    
}else if($property == DatabaseColumnsString::quoteemail)
{
    $alternative_mobiles = getAdditionalInfo($model->modelType, $record->id);
    if($alternative_mobiles) { ?>
        {{$record->$property}}
        <?php foreach($alternative_mobiles as $email) { ?>
            ", " {{$email->email_address}}
        <?php }
    }  
} else { ?>
    {{$record->$property }}
<?php } ?>