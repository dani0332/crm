<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
  configurations: Object,
  nationalities: Array,
  quoteTypes: Array,
  filters: Object,
});
const toDate = ref('2099-12-31');

const loader = ref({
  table: false,
});

// Track Inertia events
onMounted(() => {
  router.on('start', () => {
    if (isSearchOperation) {
      isSearching.value = true;
    }
  });

  router.on('finish', () => {
    isSearching.value = false;
    isSearchOperation = false;
  });
});

</script>

<template>
  <Head title="GBP Eligible Nationalities" />
  <div>
    <h2 class="text-xl font-semibold mb-1">GBP Eligible Nationalities</h2>
    <span class="text-sm">Select nationalities AND/OR predefined groups that qualify for GBP Routing</span>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="mb-4">
        <x-field label="Effective Date">
            <div class="grid sm:grid-cols-2 gap-4">
                    <DatePicker
                        name="from"
                        label="From"
                    />
                    <DatePicker
                        name="to"
                        label="To"
                        v-model="toDate"
                        disabled
                    />
            </div>
        </x-field>   
    </div>
    <div class="grid sm:grid-cols-2 gap-4 mb-4">
        <x-field label="Predefined Group Selection">
            <x-checkbox
            label="Max Price"
          />
        </x-field>
        
    </div>
    <div class="">
      <x-field label="GBP Nationality">
        <x-select
          :options="[]"
          class="w-100"
          placeholder="Select Nationality"
          filterable
        />
      </x-field>
    </div>
    <div class="flex justify-end gap-3">
      <x-button
        size="sm"
        color="#ff5e00"
        type="submit"
        :loading="isSearching"
        :disabled="isSearching"
      >
        Save
      </x-button>
    </div>
  </x-form>
</template>
