<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
    gbpNationalities: Array,
});
const toDate = ref('2099-12-31');
const fromDate = ref('');
const nationalityGroups = ref([]);
const selectedNationalityGroups = ref([]);
const loading = ref(false);
const gbpNationalities = ref([]);
const selectedNationalities = ref([]);
const notification = useToast();

// Custom function
function getNationalityGroups() {
  loading.value = true;

  axios.get(route('admin.nationality-groups')).then(response => {
    nationalityGroups.value = response.data.data;

  }).catch(error => {
    notification.error({
      title: 'Error fetching nationality groups',
      position: 'top',
    });
  }).finally(() => {
    loading.value = false;
  });
}

/*function getGbpNationalities() {
  axios.get(route('admin.gbp-nationalities')).then(response => {
    gbpNationalities.value = response.data.data;
  }).catch(error => {
    notification.error({
      title: 'Error fetching gbp nationalities',
      position: 'top',
    });
  });
}*/

function getSelectedGroupNationalities() {
  loading.value = true;

  axios.post('group-nationalities', { group_ids: selectedNationalityGroups.value }).then(response => {
    selectedNationalities.value = response.data.data;
  }).catch(error => {
    notification.error({
      title: 'Error fetching selected group nationalities',
    });
  }).finally(() => {
    loading.value = false;
  });
}

const toggleGroup = (id) => {
  if (selectedNationalityGroups.value.includes(id)) {
    selectedNationalityGroups.value =
      selectedNationalityGroups.value.filter(g => g !== id)
  } else {
    selectedNationalityGroups.value.push(id)
  }

  // If no groups are selected, clear the selected nationalities
  if (selectedNationalityGroups.value.length == 0) {
    selectedNationalities.value = [];
    return;
  }
  // Get selected group nationalities
  getSelectedGroupNationalities();
}

function onSubmit() {
  if (!validateForm()) {
    return;
  }

  loading.value = true;
  axios.post(route('admin.nationality-pool-config.save'), {
    effective_from: fromDate.value,
    canonical_nationality_code: selectedNationalities.value,
  }).then(response => {
    notification.success({
      title: 'Nationality pool configuration saved successfully',
    });
  }).catch(error => {
    notification.error({
      title: 'Error saving nationality pool configuration',
      position: 'top',
    });
  }).finally(() => {
    loading.value = false;
  });
}

function validateForm() {
  if (!fromDate.value) {
    notification.error({
      title: 'From date is required',
      position: 'top',
    });
    return false;
  }

  if (selectedNationalities.value.length === 0) {
    notification.error({
      title: 'GBP Nationality is required',
      position: 'top',
    });
    return false;
  }

  return true;
}

// Computed properties to render dropdown
/*const formattedNationalities = computed(() =>
  props.gbpNationalities.map(n => ({
    label: n.canonical_nationality_name,
    value: n.canonical_nationality_code
  }))
)*/

onMounted(() => {
  getNationalityGroups();
 // getGbpNationalities();
});

</script>

<template>
  <Head title="GBP Eligible Nationalities" />
  <div>
    <h2 class="text-xl font-semibold mb-1">GBP Eligible Nationalities</h2>
    <span class="text-xs">Select nationalities AND/OR predefined groups that qualify for GBP Routing</span>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <!-- Loader -->
  <div v-if="loading" class="absolute inset-0 bg-white/60 backdrop-blur-sm flex items-center justify-center z-10">
    <div class="w-8 h-8 border-4 border-gray-300 border-t-blue-600 rounded-full animate-spin"></div>
  </div>

    <div class="mb-4">
        <x-field label="Effective Date">
            <div class="grid sm:grid-cols-2 gap-4">
                <DatePicker
                    name="from"
                    label="From"
                    v-model="fromDate"
                    :min-date="new Date()"
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
            <div class="flex flex-col gap-1">
                <x-checkbox
                    v-for="group in nationalityGroups"
                    :key="group.id"
                    :value="group.id"
                    :label="group.group_name"
                    :model-value="selectedNationalityGroups.includes(group.id)"
                     @update:modelValue="toggleGroup(group.id)"
                    class="!mb-0"
                />
            </div>
        </x-field>
    </div>
    <div class="">
      <x-field label="GBP Nationality">
        <x-select
          :options="props.gbpNationalities"
          class="w-100"
          placeholder="Select Nationality"
          filterable
          multiple
          v-model="selectedNationalities"
        />
      </x-field>
    </div>
    <div class="flex justify-end gap-3">
      <x-button
        size="md"
        color="#ff5e00"
        type="submit"
        :loading="isSearching"
        :disabled="isSearching"
      >
        Update
      </x-button>
    </div>
  </x-form>
</template>
