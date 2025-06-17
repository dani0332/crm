<script setup>
const { isRequired, maxDateRange } = useRules();
const date = ref(null);

const onSubmit = isValid => {
  if (!isValid) return;

  let params = {};
  if (Array.isArray(date.value)) {
    params.start_date = date.value[0];
    params.end_date = date.value[1];
  } else if (typeof date.value === 'object' && date.value !== null) {
    params = date.value;
  }

  const url = route('buy-leads.request.export-data');
  window.open(url + '?' + new URLSearchParams(params).toString());
};
</script>

<template>
  <Head title="Export Buy Leads" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Export Buy Leads</h2>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit">
    <div class="grid sm:grid-cols-1 gap-3">
      <DatePicker
        range
        class="w-full"
        label="Start & End Date:"
        v-model="date"
        multi-calendars
        six-weeks
        :rules="[isRequired, maxDateRange]"
        placeholder="Select Date Range"
      />
    </div>
    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button size="md" color="primary" type="submit"> Export </x-button>
    </div>
  </x-form>
</template>

<style scoped></style>
