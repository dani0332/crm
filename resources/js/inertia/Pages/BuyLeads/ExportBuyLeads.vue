<script setup>
import axios from 'axios';

const { isRequired, maxDateRange } = useRules();
const date = ref(null);
const isLoading = ref(false);

const onSubmit = async isValid => {
  if (!isValid) return;

  let params = {};
  if (Array.isArray(date.value)) {
    params.start_date = date.value[0];
    params.end_date = date.value[1];
  } else if (typeof date.value === 'object' && date.value !== null) {
    params = date.value;
  }

  try {
    isLoading.value = true;

    // First, update employee codes and wait for success
    const updateResponse = await axios.post(
      route('buy-leads.request.update-employee-codes'),
      params,
    );

    // Only proceed with export after successful AJAX response (200)
    if (updateResponse.status === 200) {
      const exportUrl = route('buy-leads.request.export-data');
      window.open(exportUrl + '?' + new URLSearchParams(params).toString());
    }
  } catch (error) {
    console.error('Error updating employee codes:', error);
    // Do not open export window if AJAX failed
  } finally {
    isLoading.value = false;
  }
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
      <x-button size="md" color="primary" type="submit" :loading="isLoading">
        Export
      </x-button>
    </div>
  </x-form>
</template>

<style scoped></style>
