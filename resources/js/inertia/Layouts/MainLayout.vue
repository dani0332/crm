<script setup>
const page = usePage();
const user = computed(() => page.props.auth.user);
const navLinks = computed(() => page.props.sidebar);
const permissionsEnum = computed(() => page.props.permissionsEnum);
const permissions = computed(() => page.props.permissions);
const openSidebar = ref(false);

router.on('navigate', () => {
  openSidebar.value = false;
});

const onLogout = () => {
  axios.post('/logout').then(() => {
    window.location.href = '/login';
  });
};
</script>

<template>
  <main class="flex w-full min-h-screen overflow-x-clip">
    <aside
      :class="
        openSidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
      "
      class="fixed inset-y-0 left-0 z-30 flex flex-col h-screen overflow-hidden shadow-2xl transition-all bg-white lg:border-r lg:z-0 max-w-[17em] lg:max-w-[var(--sidebar-width)]"
    >
      <header
        class="border-b h-[4rem] shrink-0 flex items-center justify-center relative"
      >
        <div class="flex items-center justify-center px-2 w-full lg:px-4">
          <button
            type="button"
            class="shrink-0 lg:hidden flex items-center justify-center w-10 h-10 text-primary-500 rounded-full hover:bg-gray-500/5 focus:bg-primary-500/10 focus:outline-none"
            aria-label="Collapse sidebar"
            @click.prevent="openSidebar = !openSidebar"
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
          </button>

          <a href="/" class="block w-full">
            <img src="/image/new_logo.png" alt="IMCRM" class="w-full" />
          </a>
        </div>
      </header>

      <nav
        class="flex-1 text-[0.8rem] pb-6 font-medium overflow-x-hidden overflow-y-auto flex flex-col bg-gradient-to-b from-primary-500 to-primary-700 text-white"
      >
        <template v-for="link in navLinks">
          <template v-if="link.children.length > 0">
            <x-collapse
              show-icon
              :expanded="
                link.children.some(child => $page.url.startsWith(child.url))
              "
            >
              <template #default>
                <div class="pl-3 py-2.5 hover:bg-black/10">
                  {{ link.title }}
                </div>
              </template>

              <template #content>
                <template v-for="child in link.children">
                  <a
                    :href="child.url"
                    class="pl-3 py-2 flex gap-2 items-center hover:bg-black/10"
                    :class="{
                      '!bg-primary-800': $page.url.startsWith(child.url),
                    }"
                  >
                    <x-icon
                      :icon="
                        child.attributes.icon ? child.attributes.icon : 'box'
                      "
                    />
                    <span class="pt-1">{{ child.title }}</span>
                  </a>
                </template>
              </template>
            </x-collapse>
          </template>
          <template v-else>
            <a
              :href="link.url"
              class="pl-3 py-2.5 flex gap-2 items-center hover:bg-black/10"
              :class="{
                '!bg-primary-800': $page.props.baseUrl + $page.url == link.url,
              }"
            >
              <x-icon
                v-if="link.attributes.icon"
                :icon="link.attributes.icon"
              />
              {{ link.title }}
            </a>
          </template>
        </template>
      </nav>
    </aside>
    <div
      v-if="openSidebar"
      class="bg-black/75 backdrop-blur-sm w-full h-full fixed inset-0 z-20 lg:hidden"
      @click.prevent="openSidebar = false"
    ></div>

    <article
      class="flex-col gap-y-6 w-screen flex-1 h-full transition-all lg:pl-[var(--sidebar-width)]"
    >
      <header
        class="sticky top-0 z-20 flex h-16 w-full shrink-0 items-center border-b bg-white"
      >
        <div
          class="flex items-center justify-between w-full px-2 sm:px-4 md:px-6 lg:px-8"
        >
          <div>
            <button
              type="button"
              class="shrink-0 flex lg:hidden items-center justify-center w-10 h-10 text-primary-500 rounded-full hover:bg-gray-500/5 focus:bg-primary-500/10 focus:outline-none"
              aria-label="Open sidebar"
              @click.prevent="openSidebar = !openSidebar"
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
          </div>
          <div>
            <x-popover align="right" block>
              <x-button>{{ user.name }}</x-button>
              <template #content>
                <x-popover-container class="p-2">
                  <button class="flex gap-2 items-center" @click="onLogout">
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke-width="2"
                      stroke="currentColor"
                      class="w-6 h-6 text-red-600"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"
                      />
                    </svg>
                    <span class="text-sm font-semibold">Logout</span>
                  </button>
                </x-popover-container>
              </template>
            </x-popover>
          </div>
        </div>
      </header>
      <div class="flex-1 w-full p-4 mx-auto md:px-6 lg:px-8 max-w-full">
        <XNotifications inject-key="toast">
          <ToastArea />
          <slot />
        </XNotifications>
      </div>
    </article>
  </main>
</template>
