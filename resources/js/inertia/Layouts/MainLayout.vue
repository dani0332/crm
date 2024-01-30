<script setup>
import {XToggle} from "@indielayer/ui";

const page = usePage();
const user = computed(() => page.props.auth.user);
const navLinks = computed(() => page.props.sidebar);
const openSidebar = ref(false);

router.on('navigate', () => {
  openSidebar.value = false;
});

const onLogout = () => {
  axios.post('/logout').then(() => {
    window.location.href = '/login';
  });
};
const userStatus = ref(false);
const onStatusChange = async () => {
    await axios
        .post('/update-user-status', {
            user_status: userStatus.value,
        })
        .then(res => {

        })
        .finally(() => {

           // statusModal.show = false;
        });
};


const isButtonVisible = ref(false);
const allowedRoles = ['ADMIN','ENGINEERING','CAR_ADVISOR', 'HEALTH_ADVISOR', 'BETA_USER'];

const sameRoles = allowedRoles.filter(element => page.props.auth.roles.includes(element));

const hasAllowedRoles = sameRoles.length > 0;

const shouldShowButton = () => {
    if(hasAllowedRoles) {
        const currentTime = new Date().toLocaleString('en-US', {timeZone: 'Asia/Dubai'});
        const currentDay = new Date(currentTime).getDay();
        const currentHour = new Date(currentTime).getHours();

        // Show the button all day on Saturday and Sunday
        console.log('current day', currentDay);
        if (currentDay === 6 || currentDay === 0 || currentDay === 2) {
            return true;
        }

        // Show the button outside the range 9:00 AM to 6:30 PM on other days
        return !(currentHour >= 9 && currentHour < 18 && new Date(currentTime).getMinutes() >= 0);
    }
    return false;
};

const scheduleUpdate = () => {
    if(hasAllowedRoles) {
    const now = new Date().toLocaleString('en-US', { timeZone: 'Asia/Dubai' });
    let nextUpdate = new Date(now);
    isButtonVisible.value = shouldShowButton();

    // Calculate the time until the next scheduled update
    if (nextUpdate.getHours() < 9 || (nextUpdate.getHours() === 9 && nextUpdate.getMinutes() <= 1)) {
        nextUpdate.setHours(9, 0, 1);
    } else if (nextUpdate.getHours() >= 18 || (nextUpdate.getHours() === 18 && nextUpdate.getMinutes() >= 30)) {
        // If it's past 6:30 PM, schedule the next update for the next day at 9:00 AM
        nextUpdate.setDate(nextUpdate.getDate() + 1);
        nextUpdate.setHours(9, 0, 1);
    } else {
        // Schedule the next update for the same day at 6:30 PM
        nextUpdate.setHours(18, 30, 1);
    }

    // Schedule the next check after the calculated time difference
    setTimeout(() => {
        isButtonVisible.value = shouldShowButton();
        scheduleUpdate();
    }, nextUpdate - new Date());
    }
};

onMounted(() => {
    // Schedule the first check
    scheduleUpdate();
    userStatus.value = (user.status ==1)?true:false;
});




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
                src="/images/im_logo_21k-hi.png"
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

              <div id="headerportal"></div>
            </div>

            <div class="flex gap-3 items-center">
                <x-toggle v-model="userStatus"  v-if="isButtonVisible" @change="onStatusChange"  ></x-toggle>
              <!-- <UserStatus /> -->
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
          <slot />
        </div>
      </article>
    </XNotifications>
  </main>
</template>
