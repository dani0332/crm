<script setup>
const props = defineProps({
  departments: Array,
  lobs: Array,
});

const notification = useToast();
const { isRequired } = useRules();

const buyForm = useForm({
  quote_type: '',
  department_id: '',
  value: '',
  volume: '',
});

const loader = ref(false);
const onSubmit = isValid => {
  if (isValid) {
    buyForm.submit('post', route('admin.buy-leads.config.upsert'), {
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          notification.error({
            title: errors[key],
            position: 'top',
          });
        });
      },
      onSuccess: response => {
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

const fetchValues = () => {
  buyForm.value = '';
  buyForm.volume = '';
  loader.value = true;
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
      }
      loader.value = false;
    })
    .catch(error => {
      loader.value = false;
      notification.success({
        title: 'Error fetching configuration',
        position: 'top',
      });
    });
};

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
</script>
<template>
  <Head title="Buy Lead Configuration" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Buy Lead Configuration</h2>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 gap-4">
      <x-field label="Line of Business" required>
        <x-select
          placeholder="Select LOB"
          :options="props.lobs"
          filterable
          v-model="buyForm.quote_type"
          :rules="[isRequired]"
        ></x-select>
      </x-field>
      <x-field label="Departments">
        <x-select
          placeholder="Select Department"
          :options="props.departments"
          filterable
          v-model="buyForm.department_id"
        ></x-select>
      </x-field>
    </div>
    <p class="font-medium">Lead Pricing</p>
    <div class="grid sm:grid-cols-2 gap-4">
      <div class="grid sm:grid-cols-1 gap-4">
        <div class="grid grid-cols-3 items-center">
          <p>{{ computedValueText }}</p>
          <p>Enter Cost ({{ computedValueText }})</p>
          <x-input v-model="buyForm.value" class="!mb-0" :disabled="loader">
            <template #suffix>
              <div
                class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400"
              >
                <x-spinner v-if="loader" size="sm" class="text-primary" />
                <span v-if="!loader">AED</span>
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
          <x-input v-model="buyForm.volume" class="!mb-0" :disabled="loader">
            <template #suffix>
              <div
                class="absolute inset-y-0 right-2 my-auto mr-2 inline h-5 w-5 shrink-0 select-none text-secondary-400"
              >
                <x-spinner v-if="loader" size="sm" class="text-primary" />
                <span v-if="!loader">AED</span>
              </div>
            </template>
          </x-input>
        </div>
      </div>
    </div>

    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button
        size="md"
        color="emerald"
        type="submit"
        :loading="loader"
        :disable="loader"
      >
        Update
      </x-button>
    </div>
  </x-form>
</template>
