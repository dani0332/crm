<script setup>
const page = usePage();

const createLink = link => {
  if (link.children.length > 0) {
    return {
      label: link.title,
      active: link.active,
      items: link.children.map(createLink),
    };
  } else {
    return {
      label: link.title,
      icon: link.attributes.icon,
      value: link.url,
      href: link.url,
      active: page.props.location.startsWith(link.url) || link.active,
      ...(link.attributes.external ? { target: '_blank' } : null),
    };
  }
};

const user = computed(() => page.props.auth.user);
const pendingActivityCount = computed(()=>page.props.pendingActivityCount);
const navLinks = computed(() => page.props.sidebar);
const openSidebar = ref(false);
const bannerInfo = computed(() => {
  let { quote_route, total_count } = page.props.totalQuotesCount;

  return {
    total_count: total_count,
    quote_route: quote_route,
  };
});

router.on('navigate', () => {
  openSidebar.value = false;
});

const params = useUrlSearchParams('history');

const onLogout = () => {
  saveQueryParams();
  axios.post('/logout').then(() => {
    window.location.href = '/login';
  });
};
</script>

<template>
  <main class="flex w-full min-h-screen overflow-x-clip">
    <div
      :class="
        openSidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
      "
      class="fixed inset-y-0 left-0 z-30 flex h-dvh w-64 flex-col overflow-y-auto bg-gradient-to-b from-primary-600 to-primary-700 transition-all md:w-64"
    >
      <aside class="relative h-full w-full">
        <div
          class="sticky top-0 z-10 flex h-[63.5px] items-center justify-center border-b border-r bg-white"
        >
          <Link :href="route('dashboard.home')">
            <x-image
              :src="page.props.im_logo"
              alt="IMCRM"
              class="w-full px-2"
              width="439"
              height="66"
            />
          </Link>
        </div>
        <nav class="p-1.5">
          <ui-menu
            :items="$page.props.sidebar.map(createLink)"
            :collapseIcon="`chevronDown`"
          />
        </nav>
      </aside>
    </div>

    <div
      v-if="openSidebar"
      class="bg-black/75 backdrop-blur-sm w-full h-full fixed inset-0 z-20 lg:hidden"
      @click.prevent="openSidebar = false"
    ></div>

    <XNotifications inject-key="toast">
      <article
        class="flex-col gap-y-6 w-screen flex-1 h-full transition-all lg:pl-[var(--sidebar-width)]"
      >
        <header
          class="sticky top-0 z-40 flex h-16 w-full shrink-0 items-center border-b bg-white"
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

              <div id="headerportal"></div>
                <div class="flex gap-3">
                <div class="items-center" style="border: 1px solid #EBEBEB; padding: 5px 5px;">
                    Pending Callbacks: {{pendingActivityCount.pendingCallback}}
                </div>
                <div class="items-center" style="border: 1px solid #EBEBEB; padding: 5px 5px;">
                    Pending Whatsapp: {{pendingActivityCount.pendingWhatsapp}}
                </div>
                </div>
            </div>

            <div class="flex gap-3 items-center">
              <OnlineStatusToggle :user="user" />
              <!-- <UserStatus /> -->
                <InstantAlfredCallbackNotification/>
                <InstantAlfredWhatsappNotification/>
                <InstantAlfredCallbackReminderNotification/>
                <InstantAlfredWhatsappReminderNotification/>


              <x-popover align="right" block>
                <x-button size="sm" ghost>
                  <div class="flex gap-3 items-center">
                    <x-avatar
                      size="sm"
                      color="#999"
                      :alt="user.name"
                      :image="
                        user.profile_photo_path != null
                          ? user.profile_photo_path
                          : '/image/alfred-theme.png'
                      "
                      outlined
                      rounded
                    />
                    <span>{{ user.name }}</span>
                    <svg
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      fill="none"
                      role="presentation"
                      class="stroke-2 w-3 h-3"
                    >
                      <path d="M19 9l-7 7-7-7" />
                    </svg>
                  </div>
                </x-button>
                <template #content>
                  <x-popover-container class="p-2">
                    <button
                      class="flex gap-2 items-center px-2 group w-full"
                      @click="onLogout"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                        class="w-6 h-6 text-error-600"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"
                        />
                      </svg>
                      <span
                        class="text-sm font-semibold group-hover:text-error-600"
                      >
                        Logout
                      </span>
                    </button>
                  </x-popover-container>
                </template>
              </x-popover>
            </div>
          </div>
        </header>
        <div class="flex-1 w-full p-4 mx-auto md:px-6 lg:px-8 max-w-full">
          <ToastArea />
          <div
            v-if="bannerInfo.total_count > 0"
            class="w-full h-10 rounded bg-error-50 border border-error-500 mb-3 flex items-center justify-center text-sm max-[500px]:h-auto"
          >
            <span class="text-red-600"
              >You have
              <Link :href="bannerInfo.quote_route" class="underline">{{
                bannerInfo.total_count
              }}</Link>
              stale leads, follow up with client and update the lead status
              accordingly</span
            >
          </div>
          <Transition name="fade" mode="out-in">
            <div :key="$page.props.location">
              <slot />
            </div>
          </Transition>
        </div>
      </article>
    </XNotifications>
  </main>
</template>
