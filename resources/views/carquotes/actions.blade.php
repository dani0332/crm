
<x-action :editRoute="route('carquotes.edit', ['carquote' => $row->id])"
    :deleteRoute="route('carquotes.destroy', ['carquote' => $row->id])"
    :viewRoute="route('carquotes.destroy', ['carquote' => $row->id])" permission="carquotes"/>
