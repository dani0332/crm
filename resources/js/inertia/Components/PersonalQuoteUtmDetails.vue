<script setup>
const props = defineProps({
  uuid: {
    type: String,
    required: true,
  },
  quoteTypeId: {
    type: Number,
    required: true,
  },
});

const page = usePage();
const can = permission => useCan(permission);
const permissionEnum = page.props.permissionsEnum;

const showModal = ref(false);
const isLoading = ref(false);
const utmData = ref(null);

const UTM_FIELDS = [
  'utm_source',
  'utm_medium',
  'utm_campaign',
  'utm_content',
  'utm_term',
];

const onLoad = () => {
  isLoading.value = true;
  axios
    .post('/get-utm-details', {
      uuid: props.uuid,
      quote_type_id: props.quoteTypeId,
    })
    .then(res => {
      utmData.value = res.data?.record?.quote_detail ?? null;
      showModal.value = true;
    })
    .catch(err => {
      console.error(err);
    })
    .finally(() => {
      isLoading.value = false;
    });
};
</script>

<template>
  <x-accordion
    v-if="can(permissionEnum.QUOTE_RAW_DATA)"
    class="p-4 rounded shadow mb-6 bg-white"
  >
    <x-accordion-item>
      <h3 class="font-semibold text-primary-800 text-lg">UTM Details</h3>
      <template #content>
        <x-divider class="mb-4 mt-1" />
        <div class="text-center py-3">
          <x-button
            size="sm"
            color="primary"
            outlined
            :loading="isLoading"
            @click.prevent="onLoad"
          >
            Load UTM Data
          </x-button>
        </div>

        <AppModal v-model="showModal" show-header show-close>
          <template #header>
            <h2>UTM Details</h2>
          </template>
          <template #default>
            <div v-if="utmData" class="flex flex-wrap gap-3">
              <div
                v-for="field in UTM_FIELDS"
                :key="field"
                class="p-3 rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-all duration-300"
              >
                <div class="font-bold text-sm mb-2">{{ field }}</div>
                <div class="text-sm bg-gray-100 p-2 rounded">
                  {{ utmData[field] ?? 'Null' }}
                </div>
              </div>
            </div>
            <div v-else class="text-sm text-gray-500 py-2">
              No UTM data available.
            </div>
          </template>
        </AppModal>
      </template>
    </x-accordion-item>
  </x-accordion>
</template>
