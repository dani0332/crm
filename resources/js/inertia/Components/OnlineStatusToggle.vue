<script setup>
const notification = useToast();

const page = usePage();
const props = defineProps({
  user: {
    type: Object,
    default: {},
  },
});

const userStatus = ref(false);
const onStatusChange = async () => {
  await axios
    .post('/update-user-status', {
      user_status: userStatus.value,
    })
    .then(res => {})
    .finally(() => {});
};

const isButtonVisible = ref(false);
const allowedRoles = [
  'ADMIN',
  'ENGINEERING',
  'CAR_ADVISOR',
  'HEALTH_ADVISOR',
  'BETA_USER',
];

const sameRoles = allowedRoles.filter(element =>
  page.props.auth.roles.includes(element),
);

const hasAllowedRoles = sameRoles.length > 0;

const shouldShowButton = () => {
  if (hasAllowedRoles) {
    const currentTime = new Date().toLocaleString('en-US', {
      timeZone: 'Asia/Dubai',
    });
    const currentDay = new Date(currentTime).getDay();
    const currentHour = new Date(currentTime).getHours();

    // Show the button all day on Saturday and Sunday
    if (currentDay === 6 || currentDay === 0) {
      return true;
    }
    // Show the button outside the range 9:00 AM to 6:30 PM on other days
    if (
      currentHour < 9 ||
      currentHour > 18 ||
      (currentHour >= 18 && new Date(currentTime).getMinutes() >= 30)
    ) {
      return true;
    } else {
      return false;
    }
  }
  return false;
};

const scheduleUpdate = () => {
  if (hasAllowedRoles) {
    const now = new Date().toLocaleString('en-US', { timeZone: 'Asia/Dubai' });
    let nextUpdate = new Date(now);
    isButtonVisible.value = shouldShowButton();

    // Calculate the time until the next scheduled update
    if (
      nextUpdate.getHours() < 9 ||
      (nextUpdate.getHours() === 9 && nextUpdate.getMinutes() <= 1)
    ) {
      nextUpdate.setHours(9, 0, 1);
    } else if (
      nextUpdate.getHours() >= 18 ||
      (nextUpdate.getHours() === 18 && nextUpdate.getMinutes() >= 30)
    ) {
      // If it's past 6:30 PM, schedule the next update for the next day at 9:00 AM
      nextUpdate.setDate(nextUpdate.getDate() + 1);
      nextUpdate.setHours(9, 0, 1);
    } else {
      // Schedule the next update for the same day at 6:30 PM
      nextUpdate.setHours(18, 30, 1);
    }

    // Schedule the next check after the calculated time difference
  }
};

const intializData = () => {
  userStatus.value = props.user.status == '1' ? true : false;
};

onMounted(() => {
  scheduleUpdate();
  intializData();
});
</script>

<template>
  <span class="flex">
    <small v-if="isButtonVisible" class="mr-2">{{
      userStatus == true ? 'Online' : 'Offline'
    }}</small>
    <x-tooltip placement="bottom">
      <x-toggle
        v-model="userStatus"
        v-if="isButtonVisible"
        @change="onStatusChange"
      ></x-toggle>
      <template #tooltip>{{
        '"Offline" after working hours. This means that all incoming leads will be as per a priority queue. If you are ready to engage with leads during non-working hours, click this toggle and switch to "Online" mode such that the system will auto-adjust the lead allocations and prioritize you for any incoming hot lead over other offline members.'
      }}</template>
    </x-tooltip>
  </span>
</template>
