<x-action :editRoute="route('customer.edit', ['customer' => $row->id])"
    :deleteRoute="route('customer.destroy', ['customer' => $row->id])"
    :viewRoute="route('customer.destroy', ['customer' => $row->id])" permission="customers"/>
