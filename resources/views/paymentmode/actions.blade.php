<x-action :editRoute="route('paymentmode.edit', ['paymentmode' => $row->id])"
    :deleteRoute="route('paymentmode.destroy', ['paymentmode' => $row->id])"
    :viewRoute="route('paymentmode.show', ['paymentmode' => $row->id])" permission="payment-mode"/>
