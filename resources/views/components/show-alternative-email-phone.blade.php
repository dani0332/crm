@php
use App\Enums\quoteTypeCode;
if($property == quoteTypeCode::quotemobile)
{
    $alternative_mobiles = getAdditionalInfo($model->modelType, $record->id);
    if($alternative_mobiles) {
        echo $record->$property;
        foreach($alternative_mobiles as $mobile) {
            echo ", ". $mobile->mobile_no."<br />";
        }
    }
    
}else if($property == quoteTypeCode::quoteemail)
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