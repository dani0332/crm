<script setup>
const props = defineProps({
  departments: Array,
  lobs: Array,
  segments: Array,
  nationalities: Array,
});

const notification = useToast();
const { isRequired, isRequiredNumber } = useRules();

const buyForm = useForm({
  quote_type: '',
  department_id: '',
  segment: '',
  value: '',
  volume: '',
  nationalities: [],
});

const loader = ref(false);
const fetchLoader = ref(false);
const nationalitiesLoader = ref(false);

const onSubmit = isValid => {
  if (isValid) {
    loader.value = true;
    buyForm.submit('post', route('admin.buy-leads.config.upsert'), {
      onError: errors => {
        loader.value = false;
        Object.keys(errors).forEach(function (key) {
          notification.error({
            title: errors[key],
            position: 'top',
          });
        });
      },
      onSuccess: response => {
        loader.value = false;
        notification.success({
          title: 'Buy Lead Configuration updated successfully',
          position: 'top',
        });
      },
    });
  }
};

const computedVolumeText = computed(() => {
  return buyForm.quote_type == 'Health' ? `Entry level` : 'Volume';
});

const computedValueText = computed(() => {
  return buyForm.quote_type == 'Health' ? `Good` : 'Value';
});

const fetchNationalities = () => {
  if (buyForm.quote_type !== 'CAR_CAT_A') {
    return;
  }

  nationalitiesLoader.value = true;
  axios
    .post(route('admin.buy-leads.config.fetch-nationalities'), {
      quote_type: buyForm.quote_type,
    })
    .then(response => {
      let { nationalities } = response.data;
      buyForm.nationalities = nationalities?.map(item => item.id) ?? [];
      nationalitiesLoader.value = false;
    })
    .catch(error => {
      nationalitiesLoader.value = false;
      notification.error({
        title: 'Error fetching nationalities',
        position: 'top',
      });
    });
};

const fetchValues = () => {
  buyForm.value = '';
  buyForm.volume = '';

  // For non-CAR_CAT_A, reset nationalities as they are not department-specific
  // For CAR_CAT_A, nationalities are fetched separately when LOB changes
  if (buyForm.quote_type !== 'CAR_CAT_A') {
    buyForm.nationalities = [];
  }

  fetchLoader.value = true;
  axios
    .post(route('admin.buy-leads.config.fetch'), {
      quote_type: buyForm.quote_type,
      department_id: buyForm.department_id,
    })
    .then(response => {
      let { config } = response.data;
      if (config) {
        buyForm.value = config.value;
        buyForm.volume = config.volume;
        buyForm.segment = config.segment;
      } else {
        // TODO: For Now Setting Default Segment as SIC but need to remove this later
        buyForm.segment = 'sic';
      }
      fetchLoader.value = false;
    })
    .catch(error => {
      fetchLoader.value = false;
      notification.error({
        title: 'Error fetching configuration',
        position: 'top',
      });
    });
};

// Watch for LOB changes - fetch nationalities immediately for CAR_CAT_A
watch(
  () => buyForm.quote_type,
  newQuoteType => {
    if (newQuoteType === 'CAR_CAT_A') {
      fetchNationalities();
    } else {
      // Clear nationalities for non-CAR_CAT_A LOBs
      buyForm.nationalities = [];
    }
  },
);

// Watch for both LOB and department changes - fetch pricing config
watch(
  [() => buyForm.quote_type, () => buyForm.department_id],
  () => {
    if (buyForm.quote_type && buyForm.department_id) {
      fetchValues();
    }
  },
  {
    deep: true,
  },
);

const updatedLobs = computed(() => {
  return props.lobs.map(lob => {
    if (lob.value === 'CAR_CAT_A') {
      return {
        ...lob,
        label: 'Car Revival Cat A',
      };
    }
    return lob;
  });
});
</script>
<template>
  <Head title="Buy Lead Configuration" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Buy Lead Configuration</h2>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-3 gap-4">
      <x-select
        placeholder="Select LOB"
        :options="updatedLobs"
        filterable
        v-model="buyForm.quote_type"
        :rules="[isRequired]"
        label="Line of Business"
        required
      ></x-select>
      <x-select
        placeholder="Select Department"
        :options="props.departments"
        filterable
        v-model="buyForm.department_id"
        :rules="[isRequired]"
        label="Departments"
        required
      ></x-select>
      <x-select
        placeholder="Select Segment"
        :options="props.segments"
        filterable
        v-model="buyForm.segment"
        :disabled="fetchLoader || !buyForm.department_id || !buyForm.quote_type"
        :loading="fetchLoader"
        :rules="[isRequired]"
        label="Segment"
        required
      ></x-select>
    </div>
    <p class="font-medium">Lead Pricing</p>
    <div class="grid sm:grid-cols-2 gap-4">
      <div class="grid sm:grid-cols-1 gap-4">
        <div class="grid grid-cols-3 items-center">
          <p>{{ computedValueText }}</p>
          <p>Enter Cost ({{ computedValueText }})</p>
          <x-input
            v-model="buyForm.value"
            class="!mb-0"
            :disabled="
              fetchLoader || !buyForm.department_id || !buyForm.quote_type
            "
            :rules="[isRequiredNumber]"
          >
            <template #suffix>
              <div
                class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400"
              >
                <x-spinner v-if="fetchLoader" size="sm" class="text-primary" />
                <span v-if="!fetchLoader">AED</span>
              </div>
            </template>
          </x-input>
        </div>
      </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div class="grid sm:grid-cols-1 gap-4">
        <div class="grid grid-cols-3 items-center">
          <p>{{ computedVolumeText }}</p>
          <p>Enter Cost ({{ computedVolumeText }})</p>
          <x-input
            v-model="buyForm.volume"
            class="!mb-0"
            :disabled="
              fetchLoader || !buyForm.department_id || !buyForm.quote_type
            "
            :rules="[isRequiredNumber]"
          >
            <template #suffix>
              <div
                class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400"
              >
                <x-spinner v-if="fetchLoader" size="sm" class="text-primary" />
                <span v-if="!fetchLoader">AED</span>
              </div>
            </template>
          </x-input>
        </div>
      </div>
    </div>

    <div
      class="grid sm:grid-cols-2 gap-4"
      v-if="buyForm.quote_type == 'CAR_CAT_A'"
    >
      <div class="grid sm:grid-cols-1 gap-4">
        <p class="font-medium">CAT A - Nationalities (Per LOB Configuration)</p>
        <p class="text-sm text-gray-500 -mt-2">
          Note: Nationalities are configured per Line of Business (LOB). Changes
          will apply to all departments within this LOB.
        </p>
        <x-select
          placeholder="Select Nationalities"
          :options="props.nationalities"
          filterable
          multiple
          v-model="buyForm.nationalities"
          :rules="[isRequired]"
          :loading="nationalitiesLoader"
          :disabled="nationalitiesLoader"
        >
          <template #content-footer>
            <ui-select-actions
              @select-all="
                buyForm.nationalities = props.nationalities.map(
                  item => item.value,
                )
              "
              @clear="buyForm.nationalities = []"
            />
          </template>
        </x-select>
      </div>
    </div>

    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button
        size="md"
        color="emerald"
        type="submit"
        :loading="loader"
        :disabled="loader"
      >
        Update
      </x-button>
    </div>
  </x-form>
</template>
