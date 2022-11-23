<header class="flex-shrink-0 border-b">
  <div class="flex items-center justify-between p-2">

    <button @click="$store.sideBar.resize()" class="p-2 rounded-md focus:outline-none focus:ring hidden lg:flex">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-sky-700" :class="{'transform transition-transform rotate-180 duration-200': $store.sideBar.minimized}">
        <path fill-rule="evenodd" d="M13.28 3.97a.75.75 0 010 1.06L6.31 12l6.97 6.97a.75.75 0 11-1.06 1.06l-7.5-7.5a.75.75 0 010-1.06l7.5-7.5a.75.75 0 011.06 0zm6 0a.75.75 0 010 1.06L12.31 12l6.97 6.97a.75.75 0 11-1.06 1.06l-7.5-7.5a.75.75 0 010-1.06l7.5-7.5a.75.75 0 011.06 0z" clip-rule="evenodd" />
      </svg>
    </button>

    <button @click="$store.sideBar.toggle()" class="p-2 rounded-md focus:outline-none focus:ring flex lg:hidden">

      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-sky-700">
        <path fill-rule="evenodd" d="M2 3.75A.75.75 0 012.75 3h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 3.75zm0 4.167a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75a.75.75 0 01-.75-.75zm0 4.166a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75a.75.75 0 01-.75-.75zm0 4.167a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
      </svg>

    </button>

    <div x-data="{ showDropdown: false }" class="relative inline-block text-left my-0.5">
      <button x-on:mouseover="showDropdown = true" x-on:mouseleave="showDropdown = false" x-on:click="showDropdown = !showDropdown" type="button" class="inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-100" aria-expanded="true" aria-haspopup="true">
        {{ auth()->check() ? auth()->user()->name : 'User' }}
        <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
      </button>

      <div x-cloak x-show="showDropdown" x-on:mouseover="showDropdown = true" x-on:mouseleave="showDropdown = false" class="absolute right-0 z-10 w-40 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" tabindex="-1">
        <div class="py-1" role="none">
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-gray-800 hover:text-sky-800 block w-full px-4 py-2 text-left font-semibold text-sm" role="menuitem" tabindex="-1">
              {{ __('Log Out') }}
            </button>
          </form>
        </div>
      </div>
    </div>

  </div>
</header>