<x-action :editRoute="route('healthquotes.edit', ['healthquote' => $row->id])"
    :deleteRoute="route('healthquotes.destroy', ['healthquote' => $row->id])"
    :viewRoute="route('healthquotes.destroy', ['healthquote' => $row->id])" permission="healthquotes"/>
