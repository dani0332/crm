<script setup>

import LazyIndicativeAdditionalPrice from './Partials/IndicativeAdditionPrice.vue';
import LazyPlanDetails from './Partials/PlanDetails.vue';

const props = defineProps({
  quoteId: String,
  quoteType: String,
  sendUpdateLog: Object,
  sendUpdateOptions: Array,
  insuranceProviders: Object,
  sendUpdateStatusEnum: Array,
});

const page = usePage();
const notification = useToast();

const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;

const state = reactive({
  edit: false,
  redirectURL: ''
});

const currentOption = computed(() => {
  let cat = null;
  props.sendUpdateOptions.forEach(option => {
    if (option.childs.find(op => op.id === props.sendUpdateLog.category_id)) {
      cat = option;
    }
  });
  return cat;
});

const selectedType = computed(() => {
  return currentOption?.value?.childs.find(
    child => child.id === props.sendUpdateLog.category_id,
  );
});

const updateLogOptions = computed(() => {
  return selectedType.value?.childs.map(child => ({
    value: child.id,
    label: child.title,
    slug: child.slug
  }));
});

const isEditDisabled = computed(() => {
  return (
    props.sendUpdateLog.status === props.sendUpdateStatusEnum.UPDATE_BOOKED &&
    ['EF', 'CI', 'CIR', 'CPD'].includes(selectedType.value?.slug)
  );
})

const showIndicativeAdditionalPrice = computed(() => {
  let hasRestrictedSubType = false;

  updateLogOptions?.value.forEach(option => {
    if (['MDOM', 'MDOV', 'MPC'].includes(option.slug) && props.sendUpdateLog.option_id === option.value) {
      hasRestrictedSubType = true;
    }
  })
  return (
    selectedType?.value.slug === 'EF' && !hasRestrictedSubType
  );
});

const showPlanDetails = computed(() => {
  return (selectedType.slug === 'CFIAR' || selectedType.slug === 'COPD') && (
    ![quoteTypeCodeEnum.Car, quoteTypeCodeEnum.Travel, quoteTypeCodeEnum.Health].includes(props.quoteType) 
  ) 
})

const changeReasonOptions = computed(() => {
  return [];
}); 

const sendUpdateForm = useForm({
  notes: props.sendUpdateLog?.notes || '',
  option: props.sendUpdateLog?.option_id || null,
  change_reason: props.sendUpdateLog?.change_reason || '',
  reportable_id: props.sendUpdateLog?.reportable_id || null,
  childCategory: selectedType.value,
  status: props.sendUpdateLog?.status || '',
  reportable_type: props.sendUpdateLog?.reportable_type || '',
});

onMounted(() => {
  const params = new URLSearchParams(decodeURIComponent(page.url.split('?')[1]));
  state.redirectURL = params.get('refURL');
})

const onEdit = () => {
  if (isEditDisabled.value) {
    notification.error({
      title: 'Update already booked',
      position: 'top',
    });
  } else {
    state.edit = true
  }
}

const onUpdateLog = () => {
  sendUpdateForm.patch(
    route('send-update-logs.update', { id: props.sendUpdateLog.id }),
    {
      preserverScroll: true,
      onSuccess: ({ props }) => {
        notification.success({
          title: 'The request has been updated',
          position: 'top',
        });
        state.edit = false;
      },
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          notification.error({
            title: errors[key],
            position: 'top',
          });
        });
      },
    },
  );
};
</script>

<template>
  <Head>
    <title>Send Update {{ selectedType.title }} </title>
  </Head>
  <div>
    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex gap-2 w-100 flex-grow justify-between">
        <h3 class="text-lg font-semibold text-primary-800 capitalize">
          {{ selectedType.title }}
        </h3>
        <x-button
          color="primary"
          size="sm"
          class="mr-5"
          >
          <Link :href="state.redirectURL" tag="div">Go back to lead</Link>
          </x-button
        >
      </div>
      <x-divider class="my-4" />
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-bold text-right mr-10">SU REF ID</dt>
            <dd>{{ sendUpdateLog.code }}</dd>
          </div>
          <div class="grid sm:grid-cols-2 ml-[-250px]">
            <dt class="font-bold text-right mr-10">Notes</dt>
            <dd>
              <x-input
                v-model="sendUpdateForm.notes"
                size="xs"
                :disabled="!state.edit"
              />
            </dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <template v-if="selectedType.slug !== 'EN' && selectedType.slug !== 'CPU'">
              <dt class="font-bold text-right mr-10">Transaction Type</dt>
              <dd>{{ currentOption.title }}</dd>
            </template>
          </div>
          <div class="grid sm:grid-cols-2 ml-[-250px]">
            <dt class="font-bold text-right mr-10">Status</dt>
            <dd>{{ sendUpdateLog.status }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <template v-if="selectedType.slug !== 'CI' && selectedType.slug !== 'CIR' && selectedType.slug !== 'CPU' && selectedType.slug !== 'CPD'">
              <dt class="font-bold text-right mr-10">Sub Type</dt>
              <dd>
                <x-select
                  size="xs"
                  :disabled="!state.edit"
                  v-model="sendUpdateForm.option"
                  :options="updateLogOptions"
                />
              </dd>
            </template>
            <template v-else-if="selectedType.slug !== 'CPU' && selectedType.slug !== 'CPD'">
              <!-- <dt class="font-bold text-right mr-10">Reason</dt>
              <dd>
                <x-select
                  size="xs"
                  :disabled="!state.edit"
                  v-model="sendUpdateForm.change_reason"
                  :options="changeReasonOptions"
                />
              </dd> -->
            </template>
          </div>
        </dl>
      </div>
      <div class="flex justify-end">
        <x-button 
          size="sm" 
          @click="onEdit" 
          v-if="!state.edit"
        >
          Edit
        </x-button
        >
        <template v-else>
          <x-button
            size="sm"
            color="orange"
            @click="state.edit = false"
            class="mr-3"
            :loading="sendUpdateForm.processing"
            :disabled="sendUpdateForm.processing"
            >Cancel</x-button
          >
          <x-button
            size="sm"
            color="primary"
            @click="onUpdateLog"
            :loading="sendUpdateForm.processing"
            :disabled="sendUpdateForm.processing"
            >Update</x-button
          >
        </template>
      </div>
    </div>

    <!-- Indicative additional price comp will b displayed for all lobs except sub type MDOM, MDOV, MPC -->
    <LazyIndicativeAdditionalPrice
      v-if="showIndicativeAdditionalPrice"
      :sendUpdateLog="sendUpdateLog"
      :insuranceProviders="insuranceProviders"
      :selectedType="selectedType"
    />

    <LazyPlanDetails
      v-if="showPlanDetails"
      :sendUpdateLog="sendUpdateLog"
      :insuranceProviders="insuranceProviders"
      :selectedType="selectedType"
    />

    <AuditLogs
      :type="'App\\Models\\SendUpdateLog'"
      :id="$page.props.sendUpdateLog.id"
      :expanded="true"
    />
  </div>
</template>
