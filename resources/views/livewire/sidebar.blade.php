@php
use App\Enums\RolesEnum;
use App\Enums\PermissionsEnum;
@endphp

<aside x-transition:enter="transition transform duration-300" x-transition:enter-start="-translate-x-full opacity-30 ease-in" x-transition:enter-end="translate-x-0 opacity-100 ease-out" x-transition:leave="transition transform duration-300" x-transition:leave-start="translate-x-0 opacity-100 ease-out" x-transition:leave-end="-translate-x-full opacity-0 ease-in" class="fixed inset-y-0 z-10 flex flex-col flex-shrink-0 w-64 max-h-screen overflow-hidden transition-all transform bg-white border-r shadow-lg lg:z-auto lg:static lg:shadow-none" :class="{'-translate-x-full lg:translate-x-0': !$store.sideBar.open, 'lg:w-24 minimized': $store.sideBar.minimized}">

  <div class="flex items-center justify-between flex-shrink-0" :class="{'lg:justify-center': $store.sideBar.minimized}">
    <img src='{{ asset("image/new_logo.png") }}' alt="IMCRM" class="p-2" :class="{'lg:hidden': $store.sideBar.minimized}" width="356" height="63" />
    <img src='{{ asset("images/alfred.jpg") }}' alt="IMCRM" class="hidden mx-auto w-16 pt-3" :class="{'lg:block': $store.sideBar.minimized}" width="250" height="177" />
    <button @click="$store.sideBar.toggle()" class="p-2 rounded-md z-50 lg:hidden">
      <svg class="w-6 h-6 text-sky-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>
  </div>

  <div class="flex-1 overflow-hidden hover:overflow-y-auto bg-gradient-to-t from-sky-400 to-sky-600 text-white">
    @include('partials.sidebar')
  </div>

</aside>

@push('scripts')
<script>
  document.addEventListener('alpine:init', () => {
    Alpine.store('sideBar', {
      init() {
        this.open = window.outerWidth < 1024 ? false : true;
        this.minimized = false;
      },

      open: false,
      minimized: false,

      toggle() {
        this.open = !this.open;
      },
      resize() {
        this.minimized = !this.minimized;
      }
    })
  })

  document.addEventListener('DOMContentLoaded', () => {
    const elements = document.querySelectorAll('.side-menu li a');
    const route = window.location;

    if (elements) {
      const element = Array.from(elements).find(el => el.pathname == route.pathname && el.attributes.href.value != '#');

      if (element) {
        element.parentElement.parentElement.parentElement.classList.add('active');
        element.parentElement.classList.add('active-menu');
      }

      elements.forEach(function(el, key) {
        el.addEventListener('click', function() {
          el.parentElement.classList.toggle('active');
        });
      });
    }
  })
</script>
@endpush
