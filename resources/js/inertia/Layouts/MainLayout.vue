<script setup>
import PaymentNotification from "../Components/PaymentNotification.vue";
import PaymentExpireNotifications from "../Components/PaymentExpireNotification.vue"
import OnlineStatusToggle from "../Components/OnlineStatusToggle.vue";

const page = usePage();
const user = computed(() => page.props.auth.user);
const getAuthorisePaymentCount = computed(()=>page.props.getAuthorisePaymentCount)
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




const urls = computed(()=>{
    return `/quotes/car?page=1&segment_filter=all&payment_status_id=4`;

})

</script>

<template>
  <main class="flex w-full min-h-screen overflow-x-clip">
    <XNotifications inject-key="toast">
      <ToastArea />
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
              <img
                :src="page.props.im_logo"
                alt="IMCRM"
                class="w-full"
                width="439"
                height="66"
              />
            </a>
          </div>
        </header>

        <nav
          class="flex-1 text-[0.8rem] pb-6 font-medium overflow-x-hidden overflow-y-auto flex flex-col bg-gradient-to-b from-primary-500 to-primary-700 text-white"
        >
          <template v-for="link in navLinks">
            <template v-if="link.children.length > 0">
              <x-collapse
                :key="link.children"
                show-icon
                :expanded="
                  link.children.some(child =>
                    $page.url.startsWith(child.url),
                  ) ||
                  link.active ||
                  link.children.some(child =>
                    child.children.some(grandchild =>
                      $page.url.startsWith(grandchild.url),
                    ),
                  )
                "
              >
                <template #default>
                  <div
                    :class="{
                      'bg-black/10': link.children.some(
                        child =>
                          $page.url.startsWith(child.url) ||
                          link.children.some(child =>
                            child.children.some(grandchild =>
                              $page.url.startsWith(grandchild.url),
                            ),
                          ),
                      ),
                    }"
                    class="pl-3 py-2.5 hover:bg-black/10"
                  >
                    {{ link.title }}
                  </div>
                </template>

                <template #content>
                  <template v-for="child in link.children">
                    <template v-if="child.children.length > 0">
                      <x-collapse
                        :key="child.children"
                        show-icon
                        :expanded="
                          child.children.some(grandchild =>
                            $page.url.startsWith(grandchild.url),
                          )
                        "
                      >
                        <template #default>
                          <a
                            href="#"
                            class="pl-4 py-2.5 flex gap-2 items-center hover:bg-black/10"
                            :class="{
                              'bg-black/10': child.children.some(grandchild =>
                                $page.url.startsWith(grandchild.url),
                              ),
                            }"
                          >
                            <x-icon
                              :icon="
                                child.attributes.icon
                                  ? child.attributes.icon
                                  : 'box'
                              "
                            />
                            <span class="pt-1">{{ child.title }}</span>
                          </a>
                        </template>

                        <template #content>
                          <template
                            v-for="(grandchild, index) in child.children"
                            :key="index"
                          >
                            <a
                              :href="grandchild.url"
                              class="pl-10 py-2 flex gap-2 items-center hover:bg-black/10"
                              :class="{
                                '!bg-primary-800': $page.url.startsWith(
                                  grandchild.url,
                                ),
                              }"
                            >
                              <span class="text-primary-100"> ◉ </span>
                              <span>{{ grandchild.title }}</span>
                            </a>
                          </template>
                        </template>
                      </x-collapse>
                    </template>
                    <template v-else>
                      <a
                        :href="child.url"
                        class="pl-4 py-2 flex gap-2 items-center hover:bg-black/10"
                        :class="{
                          '!bg-primary-800':
                            $page.url.startsWith(child.url) || child.active,
                        }"
                        :key="child.url"
                      >
                        <x-icon
                          :icon="
                            child.attributes.icon
                              ? child.attributes.icon
                              : 'box'
                          "
                        />
                        <span class="pt-1">{{ child.title }}</span>
                      </a>
                    </template>
                  </template>
                </template>
              </x-collapse>
            </template>

            <template v-else-if="link.url != '' || link.children.length != 0">
              <a
                :href="link.url"
                class="pl-3 py-2.5 flex gap-2 items-center hover:bg-black/10"
                :class="{
                  '!bg-primary-800': $page.url.startsWith(link.url),
                }"
                :key="link.url"
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
            </div>

            <div class="flex gap-3 items-center">
               <OnlineStatusToggle
                    :user="user"
                />
              <!-- <UserStatus /> -->
              <PaymentNotification />
                <PaymentExpireNotifications/>

<!--                ADD BANER HERE-->
                <x-button class="w-full"  size="sm">
                <div class="items-center">

                    <Link :href="urls" style="text-decoration: underline dotted;">
                        Payment Authorised: {{getAuthorisePaymentCount}}
                    </Link>
                </div>
                </x-button>

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
          <slot />
        </div>
      </article>
    </XNotifications>
  </main>
</template>
