<script setup>
const props = defineProps({
  uuid: String,
  members: Array,
  genders: Object,
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
  insurancePlans: [],
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
      planId: createForm.plan_id,
      actualPremium: createForm.premium,
    })
    .then(res => {
      if (res.data == 200) {
        emit('success');
      } else {
        emit('error');
      }
    })
    .catch(err => {
      emit('error');
    })
    .finally(() => {
      createForm.loading = false;
    });
};

watch(
  () => createForm?.provider_id,
  value => {
    if (value) {
      options.loading = true;
      axios
        .get(
          `/insurance-provider-plans-health?insuranceProviderId=${value}&quoteUuId=${props.uuid}`,
        )
        .then(res => {
          if (res.data.length > 0) {
            options.insurancePlans = res.data;
          }
        })
        .finally(() => {
          options.loading = false;
          createForm.plan_id = null;
        });
    }
  },
);
</script>

<template>
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-x-4">
      <ComboBox
        v-model="createForm.provider_id"
        :options="
          $page.props.insuranceProviders?.map(item => ({
            value: item.id,
            label: item.text,
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
        v-model="createForm.plan_id"
        label="Plan"
        placeholder="Please Select Plan"
        :disabled="!createForm.provider_id"
        class="w-full"
        :helper="!createForm.provider_id ? 'Select a provider first' : ''"
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
        :disabled="!createForm.provider_id"
        class="w-full"
        :helper="!createForm.provider_id ? 'Select a provider first' : ''"
        :options="
          options.insurancePlans?.map(item => ({
            value: item.id,
            label: item.text,
          }))
        "
        :loading="options.loading"
        :rules="[isRequired]"
      />
      <div>
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
      </div>
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
                <x-tooltip position="bottom" class="arrow-t">
                  <span
                    class="font-semibold tracking-widest uppercase text-xs underline decoration-dotted decoration-primary-600 cursor-help"
                  >
                    Base Price
                  </span>
                  <template #tooltip> Base Price (exclusive of VAT) </template>
                </x-tooltip>
              </th>
              <th class="py-2 px-3 sticky top-0 text-left w-40 z-10">
                <x-tooltip position="bottom" class="arrow-t">
                  <span
                    class="font-semibold tracking-widest uppercase text-xs underline decoration-dotted decoration-primary-600 cursor-help"
                  >
                    Loading Price
                  </span>
                  <template #tooltip>
                    Loading Price (exclusive of VAT)
                  </template>
                </x-tooltip>
              </th>
              <th class="py-2 px-3 sticky top-0 text-left w-40 z-10">
                <x-tooltip position="bottom" class="arrow-t">
                  <span
                    class="font-semibold tracking-widest uppercase text-xs underline decoration-dotted decoration-primary-600 cursor-help"
                  >
                    Final Price
                  </span>
                  <template #tooltip> Final Price (exclusive of VAT) </template>
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
                  :rules="[isRequired, isDecimal]"
                />
              </td>
              <td class="x-table-cell px-3 py-4 align-middle">
                <x-input
                  v-model="membersPrice[index].loading_price"
                  size="sm"
                  class="!mb-0"
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

    <div class="flex justify-end">
      <x-button type="submit" color="primary" :loading="createForm.loading">
        Add Plan
      </x-button>
    </div>
  </x-form>
</template>
