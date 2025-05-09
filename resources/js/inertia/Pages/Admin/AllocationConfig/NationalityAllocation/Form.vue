<script setup>
const props = defineProps({
  configuration: Object,
  nationalities: Array,
  quoteTypes: Array,
  users: Array,
});

const { isRequired } = useRules();

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const configForm = useForm({
  id: props.configuration?.id ?? null,
  quote_type_id: props.configuration?.quote_type_id ?? '',
  nationality_id: props.configuration?.nationality_id ?? '',
  user_ids: props.configuration?.users?.map(user => user.id) ?? [],
  is_sic_enabled: props.configuration?.is_sic_enabled ?? false,
  is_active: props.configuration?.activated_at !== null,
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
    let method = isEdit.value ? 'put' : 'post';
    let url = isEdit.value
      ? route('admin.nationality-allocation-config.update', configForm.id)
      : route('admin.nationality-allocation-config.store');

    configForm.processing = true;
    configForm.submit(method, url, {
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          configForm.setError(key, errors[key]);
        });
        configForm.processing = false;
        return false;
      },
      onSuccess: () => {
        configForm.processing = false;
      },
    });
  }
}
</script>

<template>
  <Head :title="isEdit ? 'Edit Configuration' : 'Create Configuration'" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">
      {{ isEdit ? 'Edit' : 'Create' }} Nationality Allocation Configuration
    </h2>
    <div>
      <Link :href="route('admin.nationality-allocation-config.index')">
        <x-button size="sm" color="#1d83bc" tag="div">
          Configuration List
        </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 gap-4">
      <x-field label="Quote Type" required>
        <x-select
          v-model="configForm.quote_type_id"
          :options="quoteTypeOptions"
          placeholder="Select Quote Type"
          filterable
          :rules="[isRequired]"
          :error="configForm.errors.quote_type_id"
        />
      </x-field>

      <x-field label="Nationality" required>
        <x-select
          v-model="configForm.nationality_id"
          :options="nationalityOptions"
          placeholder="Select Nationality"
          filterable
          :rules="[isRequired]"
          :error="configForm.errors.nationality_id"
        />
      </x-field>

      <x-field label="Assigned Users" class="sm:col-span-2" required>
        <x-select
          v-model="configForm.user_ids"
          :options="userOptions"
          placeholder="Select Users"
          multiple
          filterable
          :rules="[isRequired]"
          :error="configForm.errors.user_ids"
          class="w-full"
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

      <div class="grid sm:grid-cols-2 gap-4 sm:col-span-2">
        <x-field label="SIC">
          <label class="flex items-center space-x-2 cursor-pointer">
            <x-toggle
              v-model="configForm.is_sic_enabled"
              color="success"
              size="lg"
            />
            <span class="text-sm text-gray-600">
              {{ configForm.is_sic_enabled ? 'Enabled' : 'Disabled' }}
            </span>
          </label>
        </x-field>

        <x-field label="Status">
          <label class="flex items-center space-x-2 cursor-pointer">
            <x-toggle
              v-model="configForm.is_active"
              color="success"
              size="lg"
            />
            <span class="text-sm text-gray-600">
              {{ configForm.is_active ? 'Active' : 'Inactive' }}
            </span>
          </label>
        </x-field>
      </div>
    </div>
    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button
        size="md"
        color="emerald"
        type="submit"
        :loading="configForm.processing"
      >
        {{ isEdit ? 'Update' : 'Create' }}
      </x-button>
    </div>
  </x-form>
</template>
