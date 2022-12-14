@extends('layouts.app_livewire')
@section('title','Car Quotes')
@section('content')
<div>
  <h2 class="text-lg font-bold mb-4">
    Car Quotes
  </h2>

  @livewire('car-quote-table')

</div>

@push('scripts')
<script>

  document.addEventListener('alpine:initialized', () => {
    
    const route = window.location;
    if (route.pathname == '/quotes/car') {
      const elements = document.querySelectorAll('.side-menu li a');
      const element = Array.from(elements).find(el => el.href === route.href);

      element.parentElement.parentElement.parentElement.classList.add('active');
      element.parentElement.classList.add('bg-orange-600');
    }
    
  })
</script>
@endpush
@endsection