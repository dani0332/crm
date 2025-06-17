<script setup>
import { ref } from 'vue';
// Import your UI components
import { useForm } from '@inertiajs/vue3';

// Dummy props for options (replace with real props/data as needed)
const props = defineProps({
  insurers: Array,
  carMakes: Array,
  nationalities: Array, // Add this to pass nationality options
});

// Car fields definition (same as in Show.vue)
const carFields = [
  {
    name: 'price_with_vat',
    label: 'Total Price',
    type: 'price',
    currency: 'AED',
    operator: '>=',
  },
  {
    name: 'car_value',
    label: 'Car Value',
    type: 'price',
    currency: 'AED',
    operator: '>=',
  },
  {
    name: 'car_make_id',
    label: 'Car Make',
    type: 'select_multiple',
    options: 'carMakes',
    operator: 'in',
  },
  {
    name: 'insurance_provider_id',
    label: 'Insurer',
    type: 'select_multiple',
    options: 'insurers',
    operator: 'in',
  },
];

// Helper to create a blank profile
const createBlankProfile = () => ({
  nationality: '',
  price_with_vat: '',
  car_value: '',
  car_make_id: [],
  insurance_provider_id: [],
});

const profiles = ref([createBlankProfile()]);

const addProfile = () => {
  profiles.value.push(createBlankProfile());
};

const removeProfile = idx => {
  if (profiles.value.length > 1) {
    profiles.value.splice(idx, 1);
  }
};
</script>

<template>
  <div>
    <h2 class="text-xl font-semibold mb-4">
      Car Profiles Configuration (Advanced)
    </h2>
    <div class="flex flex-col gap-6">
      <div
        v-for="(profile, idx) in profiles"
        :key="idx"
        class="bg-white rounded-lg shadow p-6 border border-gray-200 relative"
      >
        <div class="flex justify-between items-center mb-4">
          <div class="font-medium text-lg">Profile {{ idx + 1 }}</div>
          <button
            v-if="profiles.length > 1"
            @click="removeProfile(idx)"
            class="text-red-500 hover:text-red-700 text-sm flex items-center"
            type="button"
          >
            <i class="ri-delete-bin-line mr-1"></i> Remove
          </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium mb-1">Nationality</label>
            <x-select
              v-model="profile.nationality"
              :options="props.nationalities"
              placeholder="Select nationality"
              filterable
            />
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <template v-for="field in carFields" :key="field.name">
            <div v-if="field.type === 'select_multiple'">
              <label class="block text-sm font-medium mb-1">{{
                field.label
              }}</label>
              <x-select
                v-model="profile[field.name]"
                :options="props[field.options]"
                placeholder="Select {{ field.label }}"
                filterable
                multiple
              />
            </div>
            <div v-else>
              <label class="block text-sm font-medium mb-1">{{
                field.label
              }}</label>
              <x-input
                v-model="profile[field.name]"
                :placeholder="field.label"
                type="text"
                :suffix="field.currency ? field.currency : ''"
              />
            </div>
          </template>
        </div>
      </div>
      <div class="flex justify-end">
        <x-button color="primary" @click="addProfile" type="button">
          <i class="ri-add-line mr-1"></i> Add Profile
        </x-button>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Add any additional styling if needed */
</style>
