<script setup>
const props = defineProps({
  quoteStatusEnum: Array,
  quoteTypeId: Number,
  lostReasons: Object,
});

const page = usePage();
const dateFormat = date => {
  return useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value;
};

console.log(props.quoteTypeId);

provide('quoteStatusEnum', props.quoteStatusEnum);
const quotes = reactive({
  data: page.props.quotes || [],
  loader: false,
  searching: false,
  pages: {},
  queries: {},
});
</script>

<template>
  <div>
    <Head title="Health List ~ Card View" />
    <div class="flex justify-between items-center">
      <div class="flex items-center gap-5">
        <h2 class="text-xl font-semibold">Health List</h2>
        <span class="border-2 rounded px-3 bg-gray-200 text-sm font-medium">{{
          0
        }}</span>
      </div>

      <div class="space-x-2">
        <Link :href="route('health.index')">
          <x-button size="sm" color="#1d83bc"> List View </x-button>
        </Link>

        <Link :href="route('health.create')">
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
        :lostReasons="props.lostReasons"
        :quoteStatusEnum="props.quoteStatusEnum"
      />
    </div>
  </div>
</template>
