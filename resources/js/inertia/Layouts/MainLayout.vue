<script setup>
import OnlineStatusToggle from '../Components/OnlineStatusToggle.vue';
import PaymentExpireNotifications from '../Components/PaymentExpireNotification.vue';
import PaymentNotification from '../Components/PaymentNotification.vue';
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
      active: isActive(link),
      ...(link.attributes.external ? { target: '_blank' } : null),
    };
  }
};

const isActive = link => {
  const currentUrl = page.props.location;
  const exactMatch = new RegExp(`^${link.url}$`);
  return exactMatch.test(currentUrl) || link.active;
};

const user = computed(() => page.props.auth.user);
const pendingActivityCount = computed(() => page.props.pendingActivityCount);
const authorisePaymentCount = computed(() => page.props.authorisePaymentCount);
const checkAuthUserRole = computed(() => page.props.checkAuthUserRole);
const navLinks = computed(() => page.props.sidebar);
const openSidebar = ref(false);
const minimizeSidebar = ref(false);
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const impersonatingUser = page.props.impersonatingUser;
const bannerInfo = computed(() => {
  let { quote_route, total_count } = page.props.totalQuotesCount;

  return {
    total_count: total_count,
    quote_route: quote_route,
  };
});

router.on('navigate', () => {
  openSidebar.value = false;
  minimizeSidebar.value = false;
});

const params = useUrlSearchParams('history');

const onLogout = () => {
  saveQueryParams();
  axios.post('/logout').then(() => {
    window.location.href = '/login';
  });
};

const urls = computed(() => {
  return `/reports/payment-summary`;
});

const activitiesUrl = activityType => {
  const today = new Date();
  const filters = {
    status: '0',
    due_date_time_start: '',
    due_date_time_end: '',
    activity_type: '',
    page: 1,
    isCustom: false,
  };

  const firstDayOfWeek = new Date(
    today.setDate(today.getDate() - today.getDay() + 1),
  );
  const lastDayOfWeek = new Date(
    today.setDate(today.getDate() - today.getDay() + 7),
  );
  filters.due_date_time_start = useDateFormat(
    firstDayOfWeek,
    'DD-MM-YYYY',
  ).value;
  filters.due_date_time_end = useDateFormat(lastDayOfWeek, 'DD-MM-YYYY').value;
  filters.activity_type = activityType;
  return `/activities?due_date_time_start=${filters.due_date_time_start}&due_date_time_end=${filters.due_date_time_end}&activity_type=${filters.activity_type}&page=1&isCustom=false&status=0&redirect=1`;
};

const getHTML = (buttonText, data, activityType) => {
  return `<a target="_self" class="text-primary-500 hover:underline flex items-center space-x-1" href="${activitiesUrl(activityType)}"> ${buttonText}
    ${data}</a>`;
};

const isReceiveNotificationsEnabled = computed(() => {
  let permission = permissionsEnum.RECEIVE_NOTIFICATIONS;
  return can(permission);
});
</script>

<template>
  <main class="flex w-full min-h-screen overflow-x-clip">
    <div
      :class="
        minimizeSidebar
          ? '-translate-x-full'
          : openSidebar
            ? 'translate-x-0'
            : '-translate-x-full lg:translate-x-0'
      "
      class="fixed inset-y-0 left-0 z-40 flex h-dvh w-64 flex-col overflow-y-auto bg-gradient-to-b from-primary-600 to-primary-700 transition-all md:w-64"
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
      class="bg-black/75 backdrop-blur-sm w-full h-full fixed inset-0 z-30 lg:hidden"
      @click.prevent="openSidebar = false"
    ></div>

    <XNotifications inject-key="toast">
      <article
        :class="!minimizeSidebar ? 'lg:pl-[var(--sidebar-width)]' : ''"
        class="flex-col gap-y-6 w-screen flex-1 h-full transition-all"
      >
        <header
          class="sticky top-0 z-40 flex h-16 w-full shrink-0 items-center border-b bg-white"
        >
          <div
            class="flex items-center justify-between w-full px-2 sm:px-4 md:px-6 lg:px-8"
          >
            <div class="items-center justify-between gap-2 flex">
              <div class="items-center justify-between gap-1 hidden lg:flex">
                <x-tooltip>
                  <button
                    type="button"
                    class="text-primary-500 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg hover:bg-gray-500/5 focus:bg-primary-500/10 focus:outline-none transition"
                    :aria-label="
                      minimizeSidebar ? 'Expand sidebar' : 'Minimize sidebar'
                    "
                    @click.prevent="
                      minimizeSidebar = !minimizeSidebar;
                      openSidebar = false;
                    "
                  >
                    <x-icon
                      :icon="minimizeSidebar ? 'sideExpand' : 'sideCollapse'"
                      size="lg"
                    />
                  </button>
                  <template #tooltip>
                    {{
                      minimizeSidebar ? 'Expand sidebar' : 'Minimize sidebar'
                    }}
                  </template>
                </x-tooltip>

                <Transition name="fade" mode="out-in">
                  <Link
                    v-if="minimizeSidebar"
                    :href="route('dashboard.home')"
                    class="w-64 hidden lg:flex"
                  >
                    <x-image
                      :src="page.props.im_logo"
                      alt="IMCRM"
                      class="w-full px-2"
                      width="439"
                      height="66"
                    />
                  </Link>
                </Transition>
              </div>
              <div>
                <button
                  type="button"
                  class="shrink-0 flex lg:hidden items-center justify-center w-10 h-10 text-primary-500 rounded-full hover:bg-gray-500/5 focus:bg-primary-500/10 focus:outline-none"
                  aria-label="Open sidebar"
                  @click.prevent="
                    openSidebar = !openSidebar;
                    minimizeSidebar = false;
                  "
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
                <div class="lg:flex gap-1 hidden">
                  <x-tooltip position="top">
                    <x-button class="w-full" size="sm">
                      <div class="items-center">
                        <SanitizeHtml
                          style="text-decoration: dotted underline"
                          :html="
                            getHTML(
                              'Pending Callbacks: ',
                              pendingActivityCount.pendingCallback,
                              page.props.activityTypeEnum.CALL_BACK,
                            )
                          "
                        />
                      </div>
                    </x-button>
                    <template #tooltip>
                      <div class="font-bold">
                        InstantAlfred needs your help!
                        {{ pendingActivityCount.pendingCallback }} customers are
                        waiting for your callback. Don't forget to mark your
                        activity as DONE.
                      </div>
                    </template>
                  </x-tooltip>

                  <x-tooltip position="top">
                    <x-button class="w-full" size="sm">
                      <div class="items-center">
                        <SanitizeHtml
                          style="text-decoration: dotted underline"
                          :html="
                            getHTML(
                              'Pending WhatsApp requests: ',
                              pendingActivityCount.pendingWhatsapp,
                              page.props.activityTypeEnum.WHATS_APP,
                            )
                          "
                        />
                      </div>
                    </x-button>
                    <template #tooltip>
                      <div class="font-bold">
                        InstantAlfred needs your help!
                        {{ pendingActivityCount.pendingWhatsapp }}
                        customers are waiting for your WhatsApp message. Don't
                        forget to mark your activity as DONE.
                      </div>
                    </template>
                  </x-tooltip>
                </div>
              </div>
            </div>

            <div class="flex gap-3 items-center">
              <OnlineStatusToggle :user="user" />
              <CallBackNotification v-if="isReceiveNotificationsEnabled" />
              <PaymentNotification v-if="isReceiveNotificationsEnabled" />
              <PaymentExpireNotifications
                v-if="isReceiveNotificationsEnabled"
              />

              <x-tooltip>
                <x-button class="w-full" size="sm">
                  <div class="items-center">
                    <Link
                      v-bind:href="urls"
                      style="text-decoration: underline dotted"
                    >
                      Payment Authorised: {{ authorisePaymentCount }}
                    </Link>
                  </div>
                </x-button>
                <template #tooltip>
                  <div class="font-bold">
                    {{ authorisePaymentCount }} customers are waiting for their
                    policies - you're one step away from a sale.
                  </div>
                </template>
              </x-tooltip>

              <x-popover placement="bottom-end" block>
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
                    <a
                      v-if="impersonatingUser"
                      class="flex gap-2 items-center px-2 group mb-2"
                      :href="route('login-as.leave')"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                        class="w-6 h-6 text-success-600"
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
                        Back to {{ impersonatingUser.name }}
                      </span>
                    </a>
                    <button
                      class="flex gap-2 items-center px-2 group"
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
            <span class="text-red-600">
              You have
              <Link :href="bannerInfo.quote_route" class="underline">
                {{ bannerInfo.total_count }}
              </Link>
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
