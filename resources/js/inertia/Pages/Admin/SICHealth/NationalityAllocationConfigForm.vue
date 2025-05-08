<script setup>
const props = defineProps({
  configurations: Array,
  nationalities: Array,
  quoteTypes: Array,
  users: Array,
});

const page = usePage();
const notification = useToast();
const { isRequired } = useRules();
const errors = reactive({});

const configForm = useForm({
  quote_type_id: '',
  nationality_id: '',
  user_ids: [],
});

const nationalityOptions = computed(() => {
  return props.nationalities.map(nat => ({
    value: nat.id,
    label: nat.text,
  }));
});

const quoteTypeOptions = computed(() => {
  return props.quoteTypes.map(type => ({
    value: type.id,
    label: type.text,
  }));
});

const userOptions = computed(() => {
  return props.users.map(user => ({
    value: user.id,
    label: user.name,
  }));
});

function onSubmit(isValid) {
  if (isValid) {
    configForm.post(route('admin.nationality-allocation-config.store'), {
      onSuccess: () => {
        notification.success('Configuration saved successfully');
        configForm.reset();
      },
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          configForm.setError(key, errors[key]);
        });
      },
    });
  }
}

function deleteConfiguration(id) {
  if (confirm('Are you sure you want to delete this configuration?')) {
    useForm({}).delete(
      route('admin.nationality-allocation-config.destroy', id),
      {
        onSuccess: () => {
          notification.success('Configuration deleted successfully');
        },
      },
    );
  }
}

// Check if a configuration already exists with the same nationality and quote type
const isConfigurationExist = computed(() => {
  if (!configForm.nationality_id || !configForm.quote_type_id) return false;

  return props.configurations.some(
    config =>
      parseInt(config.nationality) === parseInt(configForm.nationality_id) &&
      parseInt(config.quote_type) === parseInt(configForm.quote_type_id),
  );
});
</script>

<template>
  <Head>
    <title>Nationality Allocation Configuration</title>
  </Head>

  <div class="card p-4 shadow-md rounded-lg mb-4">
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        Nationality Allocation Configuration
      </h2>
    </div>

    <x-divider class="my-4" />

    <!-- Configuration Form -->
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-3 gap-4">
        <x-field label="Quote Type" required>
          <x-select
            v-model="configForm.quote_type_id"
            :options="quoteTypeOptions"
            placeholder="Select Quote Type"
            filterable
            :rules="[isRequired]"
            :error="configForm?.errors.quote_type_id"
          />
        </x-field>

        <x-field label="Nationality" required>
          <x-select
            v-model="configForm.nationality_id"
            :options="nationalityOptions"
            placeholder="Select Nationality"
            filterable
            :rules="[isRequired]"
            :error="configForm?.errors.nationality_id"
          />
        </x-field>

        <x-field label="Users" required>
          <x-select
            v-model="configForm.user_ids"
            :options="userOptions"
            placeholder="Select Users"
            multiple
            filterable
            :rules="[isRequired]"
            :error="configForm?.errors.user_ids"
          >
            <template #content-footer>
              <ui-select-actions
                @select-all="
                  configForm.user_ids = userOptions.map(item => item.value)
                "
                @clear="configForm.user_ids = []"
              />
            </template>
          </x-select>
        </x-field>
      </div>

      <div v-if="isConfigurationExist" class="text-red-500 mt-2">
        A configuration with this Nationality and Quote Type already exists.
      </div>

      <div class="flex justify-end mt-4">
        <x-button
          type="submit"
          color="primary"
          :disabled="isConfigurationExist"
        >
          Save Configuration
        </x-button>
      </div>
    </x-form>

    <!-- Configuration List -->
    <div class="mt-8">
      <h3 class="text-lg font-medium mb-4">Current Configurations</h3>

      <div v-if="configurations.length === 0" class="text-gray-500">
        No configurations found.
      </div>

      <x-data-table
        v-else
        :data="configurations"
        :columns="[
          { key: 'id', label: 'ID' },
          { key: 'quote_type', label: 'Quote Type' },
          { key: 'nationality', label: 'Nationality' },
          { key: 'users', label: 'Assigned Users' },
          { key: 'actions', label: 'Actions' },
        ]"
      >
        <template #cell-users="{ item }">
          <div class="flex flex-wrap gap-1">
            <x-badge v-for="user in item.users" :key="user.id" color="gray">
              {{ user.name }}
            </x-badge>
          </div>
        </template>

        <template #cell-actions="{ item }">
          <div class="flex items-center space-x-2">
            <x-button
              size="xs"
              color="danger"
              @click="deleteConfiguration(item.id)"
              icon="trash"
            >
              Delete
            </x-button>
          </div>
        </template>
      </x-data-table>
    </div>
  </div>
</template>
