<script setup>
import Pusher from 'pusher-js';

const props = defineProps({
  quoteStatusEnum: Object,
  quoteTypeId: String,
  lostReasons: Object,
  quoteType: String,
  totalCount: {
    type: Number,
    default: 0,
  },
});

const page = usePage();

provide('quoteStatusEnum', props.quoteStatusEnum);
provide('quoteTypeId', props.quoteTypeId);
provide('lostReasons', props.lostReasons);
provide('quoteType', props.quoteType);

const quotes = reactive({
  data: page.props.quotes || [],
  loader: false,
  searching: false,
  pages: {},
  queries: {},
});

const options = {
  cluster: 'ap1',
  forceTLS: false,
};

const leadsCount = ref(props.totalCount);
const previousDate = getPreviousDate;
const pusher = new Pusher(page.props.pusherKey, options);
const channel = pusher.subscribe(
  'public.' + page.props.appEnv + '.total-leads-count',
);

const listen = () => {
  channel.bind('leads.count', function (e) {
    leadsCount.value = e.totalLeadsCount;
  });
};

onMounted(() => {
  listen();
});

onUnmounted(() => {
  channel.unbind('leads.count');
  channel.unsubscribe('public.' + page.props.appEnv + '.total-leads-count');
});
</script>

<template>
  <div>
    <Head title="Pet List ~ Card View" />
    <div class="flex justify-between items-center">
      <div class="flex items-center gap-5">
        <h2 class="text-xl font-semibold">Pet List</h2>
        <x-tooltip>
          <span class="border-2 rounded px-3 bg-gray-200 text-sm font-medium"
            >{{ leadsCount }}
          </span>
          <template #tooltip>
            <span>Total Leads received since {{ previousDate() }}</span>
          </template>
        </x-tooltip>
      </div>

      <div class="space-x-2">
        <Link :href="route('pet-quotes-list')">
          <x-button size="sm" color="#1d83bc"> List View </x-button>
        </Link>

        <Link :href="route('pet-quotes-create')">
          <x-button size="sm" color="#ff5e00" tag="div"> Create Lead </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <div
      v-if="quotes.data.length > 0"
      class="flex w-full h-[85vh] space-x-4 overflow-auto"
    >
      <LeadsCard
        class="flex flex-col flex-shrink-0 w-64 bg-gray-200 border border-gray-300"
        v-for="quote in quotes.data"
        :key="quote.id"
        :quote="quote"
        :quotes="quotes"
        :quoteTypeId="quoteTypeId"
        :quoteType="quoteType"
        :lostReasons="props.lostReasons"
        :quoteStatusEnum="props.quoteStatusEnum"
      />
    </div>
  </div>
</template>
