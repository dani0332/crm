<script setup>
const props = defineProps({
  uuid: String,
  members: Array,
  genders: Object,
  modelValue: Boolean,
});

const shown = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
});

const page = usePage();

const emit = defineEmits(['success', 'error']);

const dateFormat = date => useDateFormat(date, 'DD-MM-YYYY').value;

const membersPrice = reactive(
  props.members.map(member => ({
    member_id: member.id,
    base_price: null,
    loading_price: 0.0,
  })),
);

const totalBasePrice = computed(() => {
  return membersPrice.reduce((acc, member) => {
    return acc + parseFloat(member.base_price || 0);
  }, 0);
});

const totalLoadingPrice = computed(() => {
  return membersPrice.reduce((acc, member) => {
    return acc + parseFloat(member.loading_price || 0);
  }, 0);
});

const options = reactive({
  networks: [],
  insurancePlans: [],
  coPayments: [],
  loading: false,
});

const genderText = v => {
  return props.genders[v];
};

const memberCategoryText = memberCategoryId =>
  computed(() => {
    return page.props.memberCategories.find(
      category => category.id === memberCategoryId,
    )?.text;
  }).value;

const createForm = reactive({
  provider_id: null,
  network_id: null,
  plan_id: null,
  deductibles: null,
  premium: null,
  loading: false,
});

const { isRequired, isDecimal } = useRules();

const numFixed = num => {
  return parseFloat(num).toFixed(2);
};

const isEmptyField = ref(false);

const onSubmit = isValid => {
  if (createForm.provider_id == null) {
    isEmptyField.value = true;
  } else {
    isEmptyField.value = false;
  }

  if (!isValid) {
    return;
  }
  createForm.loading = true;
  axios
    .post('/health-plan-manual-create', {
      quoteUID: props.uuid,
      //   planId: createForm.plan_id,
      //   actualPremium: createForm.premium,
      formData: createForm,
      // members: props.members,
      membersPrice: membersPrice,
    })
    .then(res => {
      if (res.data == 200) {
        emit('success');
      } else {
        emit('error', res.data);
      }
    })
    .catch(err => {
      emit('error');
    })
    .finally(() => {
      createForm.loading = false;
    });
};

const planId = ref(null);

watch(
  () => createForm?.provider_id,
  value => {
    if (value) {
      options.loading = true;
      options.networks = [];
      options.insurancePlans = [];
      options.coPayments = [];
      axios
        .get(
          `/insurance-provider-plans-health?insuranceProviderId=${value}&quoteUuId=${props.uuid}`,
        )
        .then(res => {
          if (res.data.networks?.length > 0) {
            options.networks = res.data.networks;
          }
        })
        .catch(err => {
          emit('error');
        })
        .finally(() => {
          options.loading = false;
          //   createForm.plan_id = null;
        });
    }
  },
);

watch(
  () => createForm?.plan_id,
  value => {
    if (value) {
      planId.value = value;
    }
  },
);

watch(
  () => createForm?.network_id,
  value => {
    if (value) {
      options.loading = true;
      options.insurancePlans = [];
      options.coPayments = [];
      axios
        .get(
          `/network-plans-health?insuranceProviderId=${createForm?.provider_id}&network=${value}&quoteUuId=${props.uuid}`,
        )
        .then(res => {
          if (res.data.healthPlans?.length > 0) {
            options.insurancePlans = res.data.healthPlans;
          } else {
            options.insurancePlans = [];
          }
        })
        .catch(err => {
          emit('error');
        })
        .finally(() => {
          options.loading = false;
          createForm.plan_id = null;
        });
    }
  },
);

watch(
  () => createForm?.plan_id,
  value => {
    if (value) {
      options.loading = true;
      options.coPayments = [];
      axios
        .get(`/health-plan-copays?planId=${value}`)
        .then(res => {
          if (res.data.copays) {
            options.coPayments = res.data.copays;
          } else {
            options.coPayments = [];
          }
        })
        .catch(err => {
          emit('error');
        })
        .finally(() => {
          options.loading = false;
        });
    }
  },
);
</script>

<template>
  <x-modal
    v-model="shown"
    size="xl"
    title="Add Plan"
    show-close
    backdrop
    is-form
    @submit="onSubmit"
  >
    <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-x-4">
      <ComboBox
        v-model="createForm.provider_id"
        :options="
          $page.props.insuranceProviders?.map(item => ({
            value: item.value,
            label: item.label,
          }))
        "
        label="Provider"
        placeholder="Please Select Provider"
        :disabled="$page.props.insuranceProviders?.length == 0"
        single
        :hasError="isEmptyField"
      />

      <x-select
        v-model="createForm.network_id"
        label="Network"
        placeholder="Please Select Network"
        :disabled="!createForm.provider_id"
        class="w-full"
        :helper="!createForm.provider_id ? 'Select a provider first' : ''"
        :options="options.networks"
        :loading="options.loading"
        :rules="[isRequired]"
      />

      <x-select
        v-model="createForm.plan_id"
        label="Plan"
        placeholder="Please Select Plan"
        :disabled="!createForm.network_id"
        class="w-full"
        :helper="!createForm.provider_id ? 'Select a network first' : ''"
        :options="
          options.insurancePlans?.map(item => ({
            value: item.id,
            label: item.text,
          }))
        "
        :loading="options.loading"
        :rules="[isRequired]"
      />

      <x-select
        v-model="createForm.deductibles"
        label="Deductibles and Co-pay"
        placeholder="Select Deductibles and Co-pay"
        :disabled="!createForm.plan_id"
        class="w-full"
        :helper="!createForm.provider_id ? 'Select a plan first' : ''"
        :options="
          options.coPayments?.map(item => ({
            value: item.id,
            label: item.text,
          }))
        "
        :loading="options.loading"
        :rules="[isRequired]"
      />
      <!-- <div>
        <x-tooltip position="bottom" class="arrow-t">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600 mb-0.5"
          >
            Base Price
          </label>
          <template #tooltip>
            Base Price exclusive of VAT, Basmah and Policy fee
          </template>
        </x-tooltip>
        <x-input
          v-model="createForm.premium"
          type="text"
          placeholder="Enter Base Price"
          class="w-full"
          :rules="[isRequired, isDecimal]"
        />
      </div> -->
    </div>

    <div class="text-sm my-4">
      <div class="w-full overflow-x-auto">
        <table class="x-table w-full relative">
          <thead class="align-bottom">
            <tr class="text-sm text-gray-600 border-b">
              <th
                class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left"
              >
                Relationship
              </th>
              <th
                class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left w-28"
              >
                DOB
              </th>
              <th
                class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left"
              >
                Gender
              </th>
              <th class="py-2 px-3 sticky top-0 text-left w-40 z-10">
                <x-tooltip placement="bottom">
                  <span
                    class="font-semibold tracking-widest uppercase text-xs underline decoration-dotted decoration-primary-600 cursor-help"
                  >
                    Price
                  </span>
                  <template #tooltip>
                    <div class="whitespace-normal normal-case text-[10px]">
                      Base Price (exclusive of VAT, Basmah & Policy Fee)
                    </div>
                  </template>
                </x-tooltip>
              </th>
              <th class="py-2 px-3 sticky top-0 text-left w-40 z-10">
                <x-tooltip placement="bottom">
                  <span
                    class="font-semibold tracking-widest uppercase text-xs underline decoration-dotted decoration-primary-600 cursor-help"
                  >
                    Loading Price
                  </span>
                  <template #tooltip>
                    <div
                      class="whitespace-normal text-wrap normal-case text-[10px]"
                    >
                      <p>
                        Additional cost or fee that is added to the base price.
                        This extra charge is applied to cover specific risks or
                        factors associated with the policyholder, such as
                        pre-existing medical conditions or other higher-risk
                        situations (exclusive of VAT)
                      </p>
                    </div>
                  </template>
                </x-tooltip>
              </th>
              <th class="py-2 px-3 sticky top-0 text-left w-40 z-10">
                <x-tooltip placement="bottom">
                  <span
                    class="font-semibold tracking-widest uppercase text-xs underline decoration-dotted decoration-primary-600 cursor-help"
                  >
                    Final Price
                  </span>
                  <template #tooltip>
                    <div class="whitespace-normal normal-case text-[10px]">
                      Total Price (exclusive of VAT)
                    </div>
                  </template>
                </x-tooltip>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(member, index) in props.members"
              :key="index"
              class="border-b border-gray-200 align-top"
            >
              <td class="x-table-cell px-3 py-4 align-middle">
                {{ memberCategoryText(member.member_category_id) }}
              </td>
              <td class="x-table-cell px-3 py-4 align-middle">
                {{ dateFormat(member.dob) }}
              </td>
              <td class="x-table-cell px-3 py-4 align-middle">
                {{ genderText(member.gender) }}
              </td>
              <td class="x-table-cell px-3 py-4 align-middle">
                <x-input
                  v-model="membersPrice[index].base_price"
                  size="sm"
                  class="!mb-0 w-36"
                  type="number"
                  step="0.01"
                  onkeydown="return event.keyCode !== 69"
                  @paste.prevent
                  :rules="[isRequired, isDecimal]"
                />
              </td>
              <td class="x-table-cell px-3 py-4 align-middle">
                <x-input
                  v-model="membersPrice[index].loading_price"
                  size="sm"
                  class="!mb-0"
                  type="number"
                  step="0.01"
                  onkeydown="return event.keyCode !== 69"
                  @paste.prevent
                  :rules="[isDecimal]"
                />
              </td>
              <td class="x-table-cell px-3 py-4 align-middle">
                <x-input
                  :value="
                    numFixed(
                      Number(membersPrice[index].base_price) +
                        Number(membersPrice[index].loading_price),
                    )
                  "
                  size="sm"
                  disabled
                  class="!mb-0"
                />
              </td>
            </tr>
            <tr class="border-b border-gray-200">
              <td class="px-3 py-4">
                <span class="text-sm text-gray-600 font-semibold">
                  Total Base Price
                </span>
              </td>
              <td></td>
              <td></td>
              <td class="px-3 py-4">
                <x-input
                  :value="numFixed(totalBasePrice)"
                  size="sm"
                  disabled
                  class="!mb-0"
                />
              </td>
              <td class="px-3 py-4">
                <x-input
                  :value="numFixed(totalLoadingPrice)"
                  size="sm"
                  disabled
                  class="!mb-0"
                />
              </td>
              <td class="px-3 py-4">
                <x-input
                  :value="numFixed(totalBasePrice + totalLoadingPrice)"
                  size="sm"
                  disabled
                  class="!mb-0"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <template #actions>
      <div class="flex justify-end">
        <x-button type="submit" color="emerald" :loading="createForm.loading">
          Add Plan
        </x-button>
      </div>
    </template>
  </x-modal>
</template>
