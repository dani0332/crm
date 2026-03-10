<script setup>
import { onMounted, ref, nextTick } from 'vue';
import NationalityPoolScheduledConfigurations from './NationalityPoolScheduledConfigurations.vue';
import NationalityPoolAuditLogs from './NationalityPoolAuditLogs.vue';
const nationalityPoolConfigurations = ref([]);
const toDate = ref('2099-12-31'); // As suggested by the business
const fromDate = ref();
const nationalityGroups = ref([]);
const selectedNationalityGroups = ref([]);
const loading = ref(false);
const gbpNationalities = ref([]);
const selectedNationalities = ref();
const individualNationalities = ref([]);
const scheduledConfigurationsRef = ref(null);
const auditLogsRef = ref(null);
const notification = useToast();

// Custom functions
function getData(id = null) {
  loading.value = true;

  axios.get(route('admin.nationality-pool-config.data', { id })).then(response => {
    // Assign only forst time, avoid reassigning on edit
    if (nationalityGroups.value.length == 0) {
      nationalityGroups.value = response.data.groups;
    }
    if (gbpNationalities.value.length == 0) {
      gbpNationalities.value = response.data.nationalities;
    }
    nationalityPoolConfigurations.value = response.data.nationalityPoolConfigurations;

    // Populate form fields
    fromDate.value = new Date(nationalityPoolConfigurations.value?.effective_from);
    selectedNationalityGroups.value = nationalityPoolConfigurations.value?.health_nationality_group_ids?.split(',').map(Number) || [];
    selectedNationalities.value = nationalityPoolConfigurations.value?.canonical_nationality_codes?.split(',') || [];
  }).catch(error => {
    notification.error({
      title: 'Error fetching data',
      position: 'top',
    });
    console.log(error);
  }).finally(() => {
    loading.value = false;
  });
}

function getSelectedGroupNationalities() {
  loading.value = true;

  axios.post('group-nationalities', { group_ids: selectedNationalityGroups.value }).then(response => {
     selectedNationalities.value = [...new Set([...individualNationalities.value, ...response.data.data])];
  }).catch(error => {
    notification.error({
      title: 'Error fetching selected group nationalities',
      position: 'top',
    });
  }).finally(() => {
    loading.value = false;
  });
}

const addNationality = (newValues) => {
  // Store individually selected nationalities
  individualNationalities.value.push(newValues[newValues.length - 1]);

  const set = new Set(individualNationalities.value);
  individualNationalities.value = newValues.filter(v => set.has(v));
}

function onSubmit() {
  if (!validateForm()) {
    return;
  }

  loading.value = true;
  axios.post(route('admin.nationality-pool-config.save'), {
    effective_from: fromDate.value,
    health_nationality_group_ids: selectedNationalityGroups.value,
    canonical_nationality_codes: selectedNationalities.value,
  }).then(response => {
    notification.success({
      title: 'Nationality pool configuration saved successfully',
      position: 'top',
    });
    
    // Reload logs
    scheduledConfigurationsRef.value?.loadData();
    auditLogsRef.value?.loadData();
  }).catch(error => {
    notification.error({
      title: error.response.data.error,
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

onMounted(() => {
  getData();
});

watch(selectedNationalityGroups, (newVal) => {
  getSelectedGroupNationalities();
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
              <label v-for="group in nationalityGroups" :key="group.id" class="flex items-center gap-2">
                <input
                  type="checkbox"
                  :value="group.id"
                 style="accent-color:rgb(29 131 188 / 1)"
                  v-model="selectedNationalityGroups"
                  @change="toggleGroup"
                />
                {{ group.group_name }}
            </label>
            </div>
        </x-field>
    </div>
    <div class="">
      <x-field label="GBP Nationality">
        <x-select
          :options="gbpNationalities"
          class="w-100"
          placeholder="Select Nationality"
          filterable
          multiple
          v-model="selectedNationalities"
          @update:modelValue="addNationality"
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

<NationalityPoolScheduledConfigurations ref="scheduledConfigurationsRef" @edit-config="getData" />
<NationalityPoolAuditLogs ref="auditLogsRef" />
</template>
