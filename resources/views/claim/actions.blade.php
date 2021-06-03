<x-action :editRoute="route('claims.edit', ['claim' => $row->id])"
    :deleteRoute="route('claims.destroy', ['claim' => $row->id])"
    :viewRoute="route('claims.show', ['claim' => $row->id])" permission="claim"/>
