<script setup>
const notification = useNotifications('toast');
const props = defineProps({
  record: Object,
  insuranceProviders: Array,
  availablePlans: Array,
});

const page = usePage();

const emit = defineEmits(['onLoadAvailablePlansData']);

const isEmptyField = ref(false);

const { isRequired, isNumber } = useRules();

const quotePlansTable = reactive({
  columns: [
    { text: 'Provider Name', value: 'providerName' },
    { text: 'Plan Name', value: 'name' },
    { text: 'Repair Type', value: 'repairType' },
    { text: 'Price with VAT.', value: 'premiumWithVat' },
  ],
});

const totalPriceVAT = computed(() => {
  let vat = 0;
  props.availablePlans?.value.forEach(item => {
    item.addons.forEach(addon => {
      addon.carAddonOption.forEach(option => {
        if (option.isSelected && option.price != 0) {
          vat += option.price + option.vat;
        }
      });
    });
  });
  return vat;
});

const addPlanForm = useForm({
  car_quote_uuid: page.props.record.uuid,
  is_disabled: 0,
  is_create: 1,
  repair_type_comp: '',
  insurance_provider_id: '',
  insurer_quote_no: '',
  car_plan_id: null,
  actual_premium: null,
  car_value: null,
  excess: null,
});

const insuranceProviderOptions = computed(() => {
  return page.props.insuranceProviders.map(provider => ({
    value: provider.id,
    label: provider.text,
  }));
});

const insuranceProviderPlanOptions = ref([]);

const setCarPlans = () => {
  page.processing = true;
  const id = addPlanForm.insurance_provider_id;
  axios
    .get(
      `/insurance-provider-plans?insuranceProviderId=${id}&quoteUuId=${page.props.record.uuid}`,
    )
    .then(({ data }) => {
      insuranceProviderPlanOptions.value = data.map(plan => ({
        value: plan.id,
        label: plan.plan_name,
      }));
      page.processing = false;
    })
    .catch(error => {
      console.error('Error fetching insurance provider plans:', error);
    });
};

const creatQuotePlan = isValid => {
  if (
    addPlanForm.insurance_provider_id == '' ||
    addPlanForm.insurance_provider_id == null
  ) {
    isEmptyField.value = true;
  } else {
    isEmptyField.value = false;
  }

  if (!isValid) return;
  addPlanForm.post(`${page.props.record.uuid}/car-plan-manual-process`, {
    preserveScroll: true,
    onSuccess: () => {
      notification.success({
        title: 'Car Quote Plan created successfully',
        position: 'top',
      });
      emit('onLoadAvailablePlansData');
    },
  });
};

const validateDecimal = event => {
  if (
    event.key === '.' ||
    event.key === 'Backspace' ||
    event.key === 'Delete'
  ) {
    return;
  }
  const regex = /^\d+(\.\d{0,2})?$/;
  if (!regex.test(event.key)) {
    event.preventDefault();
  }
};

const getAddonVat = item => {
  let addonVat = 0;
  item.addons.forEach(addon => {
    addon.carAddonOption.forEach(option => {
      if (option.isSelected && option.price != 0) {
        addonVat += parseInt(option.price) + option.vat;
      }
    });
  });
  return addonVat;
};
</script>
<template>
  <x-form @submit="creatQuotePlan" :auto-focus="false">
    <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
      <div class="w-full md:w-1/2">
        <div class="flex flex-col gap-4">
          <x-field label="Insurance Provider" required>
            <ComboBox
              v-model="addPlanForm.insurance_provider_id"
              :single="true"
              :options="insuranceProviderOptions"
              :hasError="isEmptyField"
              placeholder="Select Insurance provider"
              @update:modelValue="setCarPlans"
            />
          </x-field>
        </div>
      </div>
      <div class="w-full md:w-1/2">
        <div class="flex flex-col gap-4">
          <x-field label="Plan" required>
            <x-select
              v-model="addPlanForm.car_plan_id"
              :rules="[isRequired]"
              :options="insuranceProviderPlanOptions"
              placeholder="Select plan"
              class="w-full"
              :loading="page.processing"
            />
          </x-field>
        </div>
      </div>
    </div>
    <div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
      <div class="w-full md:w-1/3">
        <x-field label="Price without vat" required>
          <x-input
            v-model="addPlanForm.actual_premium"
            :rules="[isRequired]"
            class="w-full"
            placeholder="Enter Price without vat"
            type="number"
            step="any"
          />
        </x-field>
      </div>
      <div class="w-full md:w-1/3">
        <x-field label="Car value" required>
          <x-input
            v-model="addPlanForm.car_value"
            :rules="[isRequired]"
            class="w-full"
            placeholder="Enter Car value"
            type="number"
            step="any"
          />
        </x-field>
      </div>
      <div class="w-full md:w-1/3">
        <x-field label="Excess" required>
          <x-input
            v-model="addPlanForm.excess"
            :rules="[isRequired]"
            class="w-full"
            placeholder="Enter excess"
            type="number"
            step="any"
          />
        </x-field>
      </div>
      <div class="w-full md:w-1/3">
        <x-field label="Insurer Quote Number" required>
          <x-input
            v-model="addPlanForm.insurer_quote_no"
            :rules="[isRequired]"
            class="w-full"
            placeholder="Enter Insurer Quote Number"
            maxlength="50"
          />
        </x-field>
      </div>
    </div>

    <div class="text-right space-x-4">
      <x-button
        size="sm"
        color="emerald"
        :loading="addPlanForm.processing"
        type="submit"
      >
        Add Plan
      </x-button>
    </div>
  </x-form>
  <div class="flex justify-between items-center mb-4">
    <h3 class="font-semibold text-primary-800 text-lg">Quoted Plans</h3>
  </div>
  <DataTable
    table-class-name="tablefixed compact"
    :headers="quotePlansTable.columns"
    :items="props.availablePlans || []"
    show-index
    border-cell
    hide-rows-per-page
    hide-footer
  >
    <template #item-premiumWithVat="item">
      {{
        parseFloat(item.discountPremium + item.vat + getAddonVat(item)).toFixed(
          2,
        )
      }}
    </template>
  </DataTable>
</template>
