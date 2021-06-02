<x-action :editRoute="route('handler.edit', ['handler' => $row->id])"
    :deleteRoute="route('handler.destroy', ['handler' => $row->id])"
    :viewRoute="route('handler.show', ['handler' => $row->id])" permission="handler"/>
