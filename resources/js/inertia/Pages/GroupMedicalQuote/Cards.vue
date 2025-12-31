<script setup>
const props = defineProps({
  quotes: Array,
  quoteStatusEnum: Array,
  lostReasons: Array,
  leadStatuses: Array,
  advisors: Array,
  teams: Array,
  insuranceTypeOptions: Array,
  quoteTypeId: Number,
  quoteType: String,
  totalCount: Number,
  areBothTeamsPresent: Boolean,
  is_renewal: String,
  business_type_of_insurance_id: Number,
});

const page = usePage();
const hasRole = role => useHasRole(role);
const hasAnyRole = roles => useHasAnyRole(roles);
const rolesEnum = page.props.rolesEnum;

provide('quoteStatusEnum', props.quoteStatusEnum);
provide('quoteTypeId', props.quoteTypeId);
provide('lostReasons', props.lostReasons);
provide('quoteType', props.quoteType);
provide('business_type_of_insurance_id', props.business_type_of_insurance_id);

const quotes = reactive({
  data: page.props.quotes || [],
  loader: false,
  searching: false,
  pages: {},
  queries: {},
});

watch(
  () => page.props.quotes,
  () => {
    quotes.data = page.props.quotes;
  },
  { deep: true },
);
</script>

<template>
  <Head title="Business Quote ~ Card View" />
  <StickyHeader>
    <template v-slot:header>
      <h2 class="text-xl font-semibold">Group Medical List</h2>
    </template>
    <template #default>
      <Link :href="route('amt.index')">
        <x-button size="sm" color="#1d83bc"> List View </x-button>
      </Link>

      <Link :href="route('amt.create')">
        <x-button size="sm" color="#ff5e00" tag="div"> Create Lead </x-button>
      </Link>
    </template>
  </StickyHeader>
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
</template>
