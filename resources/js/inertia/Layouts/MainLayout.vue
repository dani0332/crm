<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
  <main class="flex w-full min-h-screen overflow-x-clip">
    <aside
      class="fixed inset-y-0 left-0 z-20 flex flex-col h-screen overflow-hidden shadow-2xl transition-all bg-white lg:border-r lg:z-0 translate-x-0 max-w-[20em] lg:max-w-[var(--sidebar-width)]"
    >
      <header
        class="border-b h-[4rem] shrink-0 flex items-center justify-center relative"
      >
        <div class="flex items-center justify-center px-6 w-full lg:px-4">
          <!-- <button
            type="button"
            class="shrink-0 hidden lg:flex items-center justify-center w-10 h-10 text-primary-500 rounded-full hover:bg-gray-500/5 focus:bg-primary-500/10 focus:outline-none"
            aria-label="Collapse sidebar"
          >
            <svg
              class="h-6 w-6"
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                d="M20.25 7.5L16 12L20.25 16.5M3.75 12H12M3.75 17.25H16M3.75 6.75H16"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              ></path>
            </svg>
          </button> -->

          <a href="/" class="block w-full">
            <img src="/images/logo.png" alt="IMCRM" class="w-full" />
          </a>
        </div>

        <button
          type="button"
          class="shrink-0 flex items-center justify-center w-10 h-10 text-primary-500 rounded-full hover:bg-gray-500/5 focus:bg-primary-500/10 focus:outline-none"
          aria-label="Collapse sidebar"
          style="display: none"
        >
          <svg
            class="w-6 h-6"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="2"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"
            ></path>
          </svg>
        </button>
      </header>

      <nav
        class="flex-1 py-6 text-sm font-medium overflow-x-hidden overflow-y-auto flex flex-col bg-gradient-to-b from-primary-500 to-primary-700 text-white"
      >
        <Link href="/"><div class="p-2">Home</div></Link>
        <x-collapse
          show-icon
          :expanded="$page.component === 'HealthQuote/Index'"
        >
          <template #default><div class="p-2">Personal Quotes</div></template>
          <template #content>
            <Link href="/quotes/car" class="py-2 px-3 list-item">
              Car Quotes
            </Link>
            <Link
              href="/quotes/health"
              class="py-2 px-3 list-item"
              :class="{
                'bg-primary-800': $page.component === 'HealthQuote/Index',
              }"
            >
              Health Quotes
            </Link>
          </template>
        </x-collapse>
      </nav>
    </aside>
    <article
      class="flex-col gap-y-6 w-screen flex-1 h-full transition-all lg:pl-[var(--sidebar-width)]"
    >
      <header
        class="sticky top-0 z-10 flex h-16 w-full shrink-0 items-center border-b bg-white"
      >
        <div
          class="flex items-center justify-end w-full px-2 sm:px-4 md:px-6 lg:px-8"
        >
          <Link href="/logout" method="post" as="button" type="submit">
            Logout
          </Link>
        </div>
      </header>
      <div class="flex-1 w-full p-4 mx-auto md:px-6 lg:px-8 max-w-full">
        <slot />
      </div>
    </article>
  </main>
</template>
