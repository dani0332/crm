<x-action :editRoute="route('partner.edit', ['partner' => $row->id])"
    :deleteRoute="route('partner.destroy', ['partner' => $row->id])"
    :viewRoute="route('partner.destroy', ['partner' => $row->id])" permission="partners" />
