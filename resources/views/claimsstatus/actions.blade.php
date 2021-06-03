<x-action :editRoute="route('claimsstatus.edit', ['claimsstatus' => $row->id])"
    :deleteRoute="route('claimsstatus.destroy', ['claimsstatus' => $row->id])"
    :viewRoute="route('claimsstatus.show', ['claimsstatus' => $row->id])" permission="claims-status"/>