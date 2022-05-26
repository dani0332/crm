@php
if($property == 'mobile_no')
{
    $alternative_mobiles = getAdditionalInfo($model->modelType, $record->id);
    if($alternative_mobiles) {
        echo $record->$property;
        foreach($alternative_mobiles as $mobile) {
            echo ", ". $mobile->mobile_no."<br />";
        }
    }
    
}else if($property == 'email')
{
    $alternative_mobiles = getAdditionalInfo($model->modelType, $record->id);
    if($alternative_mobiles) {
        echo $record->$property;
        foreach($alternative_mobiles as $email) {
            echo ", ". $email->email_address."<br />";
        }
    }
    
} else
    echo $record->$property;
    
@endphp