<script setup>
import LazyPlanDetails from './Partials/PlanDetails.vue';
import LazyPolicyDetails from './Partials/PolicyDetails.vue';
import LazyBookingDetails from './Partials/BookingDetails.vue';

const props = defineProps({
  quoteType: String,
  sendUpdateLog: Object,
  sendUpdateOptions: Array,
  insuranceProviders: Object,
  sendUpdateStatusEnum: Object,
  quote: Object,
  indicativePrice: Object
});

const page = usePage();
const notification = useToast();

const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;

const state = reactive({
  edit: false,
  redirectURL: ''
});

const selectedCategory = computed(() => {
  let category = null;
  for (let mainCategory of props.sendUpdateOptions) {
    for (let subCategory of mainCategory.childs || []) {
      if (subCategory.id === props.sendUpdateLog.category_id) {
        category = {...mainCategory};
        delete category.childs;

        category.subCategory = {...subCategory};
        delete category.subCategory.childs;

        if (subCategory.childs?.length) {
          for (let option of subCategory.childs || []) {
            if (option.id === props.sendUpdateLog.option_id) {
              category.subCategory.option = {...option};
            }
          }
          category.subCategory.options = [...subCategory.childs];
        } else {
          category.subCategory.option = null;
          category.subCategory.options = [];
        }        
      }
    }
  }

  return category;
})

const updateLogOptions = computed(() => {
  return selectedCategory?.value?.subCategory.options.map(child => ({
    value: child.id,
    label: child.title,
    slug: child.slug
  }));
});

const isUpdateBooked = computed(() => {
  return (
    props.sendUpdateLog.status === props.sendUpdateStatusEnum.UPDATE_BOOKED &&
    ['EF', 'CI', 'CIR', 'CPD'].includes(selectedCategory?.value?.subCategory.slug)
  );
})

const changeReasonOptions = computed(() => {
  return [];
}); 

const isPolicyDetailsEnabled = computed(() => {
  return (
    (selectedCategory?.value?.subCategory.slug === 'EF' && selectedCategory?.value?.subCategory.option.slug === 'PPE') ||
    selectedCategory?.value?.subCategory.slug === 'CIR' ||
    selectedCategory?.value?.subCategory.slug === 'CPD'
  );
})

const sendUpdateForm = useForm({
  notes: props.sendUpdateLog?.notes || '',
  option_id: props.sendUpdateLog?.option_id || null,
  change_reason: props.sendUpdateLog?.change_reason || '',
  reportable_id: props.sendUpdateLog?.reportable_id || null,
  quote_type_id: props.sendUpdateLog?.quote_type_id || null,
  reportable_uuid: props.sendUpdateLog?.reportable_uuid || null,
  personal_quote_id: props.sendUpdateLog?.personal_quote_id || null,
  childCategory: selectedCategory?.value?.subCategory,
  status: props.sendUpdateLog?.status || '',
});

onMounted(() => {
  const params = new URLSearchParams(decodeURIComponent(page.url.split('?')[1]));
  state.redirectURL = params.get('refURL');
})

const onEdit = () => {
  if (isUpdateBooked.value) {
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
    <title>Send Update {{ selectedCategory.subCategory.title }} </title>
  </Head>
  <div>
    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex gap-2 w-100 flex-grow justify-between">
        <h3 class="text-lg font-semibold text-primary-800 capitalize">
          {{ selectedCategory.subCategory.title }}
        </h3>
        <Link :href="state.redirectURL">
          <x-button color="primary" size="sm" class="mr-5">Go back to lead</x-button>
        </Link>
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
            <template v-if="selectedCategory.subCategory.slug !== 'EN' && selectedCategory.subCategory.slug !== 'CPU'">
              <dt class="font-bold text-right mr-10">Transaction Type</dt>
              <dd>{{ selectedCategory.title }}</dd> 
            </template>
          </div>
          <div class="grid sm:grid-cols-2 ml-[-250px]">
            <dt class="font-bold text-right mr-10">Status</dt>
            <dd>{{ sendUpdateLog.status }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <template v-if="selectedCategory.subCategory.slug !== 'CI' && selectedCategory.subCategory.slug !== 'CIR' && selectedCategory.subCategory.slug !== 'CPU' && selectedCategory.subCategory.slug !== 'CPD'">
              <dt class="font-bold text-right mr-10">Sub Type</dt>
              <dd>
                <x-select
                  size="xs"
                  :disabled="!state.edit"
                  v-model="sendUpdateForm.option_id"
                  :options="updateLogOptions"
                />
              </dd>
            </template>
            <template v-else-if="selectedCategory.subCategory.slug !== 'CPU' && selectedCategory.subCategory.slug !== 'CPD'">
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

    <!-- Indicative additional price & Plan details comp -->
    <LazyPlanDetails
      :sendUpdateLog="sendUpdateLog"
      :updateLogOptions="updateLogOptions"
      :selectedCategory="selectedCategory"
      :insuranceProviders="insuranceProviders"
      :quoteType="quoteType"
      :isUpdateBooked="isUpdateBooked"
    />

    <LazyPolicyDetails
      v-if="isPolicyDetailsEnabled"
      :sendUpdateLog="sendUpdateLog"
      :insuranceProviders="insuranceProviders"
      :selectedCategory="selectedCategory"
      :quote="quote"
      :isUpdateBooked="isUpdateBooked"
    />

    <!-- Documents Component goes here -->


    <!-- EF || CI || CIR, it will show 2 sections for booking details with diff titles || 
      CPD, it will show 2 sections for booking details with diff titles 
    -->


    <!-- CIR titles 
    1. Booking Details - New Policy
    2. Booking Details - Previous Policy
    -->

    <!-- CPD titles 
    1. Booking Details - Reversal Entry 
    2. Booking Details - New Entry
    -->
    <LazyBookingDetails
      :sendUpdateLog="sendUpdateLog"
      :insuranceProviders="insuranceProviders"
      :selectedCategory="selectedCategory"
      :quote="quote"
      :quoteType="quoteType"
      :isUpdateBooked="isUpdateBooked"
    />

    <AuditLogs
      :type="'App\\Models\\SendUpdateLog'"
      :id="$page.props.sendUpdateLog.id"
      :expanded="true"
    />
  </div>
</template>
