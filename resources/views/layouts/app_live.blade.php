<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" href="{{ asset('image/favicon.ico') }}">
  <title>@yield('title') | {{config('constants.APP_NAME')}}</title>

  <link href="{{ asset('css/livewire.css') }}" rel="stylesheet">
  @livewireStyles
  <style>
    [x-cloak] {
      display: none !important;
    }
  </style>
</head>

<body class="antialiased text-gray-900 bg-white">
  <div class="flex h-screen overflow-y-hidden bg-white" x-data x-init="$refs.loading.classList.add('hidden')">

    <div x-ref="loading" class="fixed inset-0 z-50 flex items-center justify-center text-white font-bold text-xl bg-black bg-opacity-50 backdrop-blur-md">
      Loading Data...
    </div>

    <div @click="$store.sideBar.toggle()" x-show.in.out.opacity="$store.sideBar.open" class="fixed inset-0 z-10 bg-black bg-opacity-20 backdrop-blur-md lg:hidden"></div>

    @include('partials.sidebar_live')

    <div class="flex flex-col flex-1 h-full overflow-hidden">

      @include('partials.header')

      @include('partials.messages')

      <main class="flex-1 max-h-full p-5 overflow-hidden overflow-y-scroll">
        @yield('content')

        <footer class="mt-12">
          <p class="text-xs"> © AFIA Insurance Brokerage Services LLC, registration no. 85, under UAE Insurance Authority</p>
        </footer>
      </main>

    </div>

  </div>

  <script src="{{ asset('js/alpine.js') }}" defer></script>
  @livewireScripts
  <script defer>
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
  </script>

</body>

</html>