<script setup>
const props = defineProps({
  configuration: Object,
  nationalities: Array,
  quoteTypes: Array,
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
  should_skip_sic: props.configuration?.should_skip_sic ?? false,
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

// Initialize userOptions with existing users on edit to prevent display issues
const userOptions = ref(
  props.configuration?.users?.map(user => ({
    value: user.id,
    label: user.name,
  })) || [],
);
const advisorsLoading = ref(false);

// Computed property for dynamic placeholder text
const advisorPlaceholder = computed(() => {
  if (advisorsLoading.value) return 'Loading advisors...';
  if (!configForm.quote_type_id)
    return 'Select Quote Type first to load advisors';
  if (userOptions.value.length === 0)
    return 'No advisors found for this Quote Type';
  return 'Select advisors from the list';
});

/**
 * Fetch advisors based on the selected quote type
 */
const fetchAdvisors = quoteTypeId => {
  if (!quoteTypeId) return;

  advisorsLoading.value = true;
  const quoteType = props.quoteTypes.find(type => type.id === quoteTypeId);

  if (!quoteType) {
    advisorsLoading.value = false;
    return;
  }

  // Use the web route
  axios
    .post('/advisors/by-quote-type', {
      quote_type: quoteType.code ? quoteType.code : quoteType.text,
    })
    .then(response => {
      if (
        response.data.success &&
        response.data.data &&
        response.data.data.length > 0
      ) {
        // Get current user selections
        const currentSelections = configForm.user_ids || [];

        // Create a map of new options
        const newOptions = response.data.data.map(user => ({
          value: user.id,
          label: user.name,
        }));

        // If we're in edit mode and have selections, ensure they exist in options
        if (isEdit.value && currentSelections.length > 0) {
          // Get existing selected users that aren't in the new options
          const existingSelectedUsers =
            props.configuration?.users
              ?.filter(
                user =>
                  currentSelections.includes(user.id) &&
                  !newOptions.some(option => option.value === user.id),
              )
              .map(user => ({
                value: user.id,
                label: user.name,
              })) || [];

          // Combine existing selected users with new options
          userOptions.value = [...existingSelectedUsers, ...newOptions];
        } else {
          userOptions.value = newOptions;
        }
      } else {
        // If no users returned but we have selections in edit mode, preserve them
        if (
          isEdit.value &&
          configForm.user_ids?.length > 0 &&
          props.configuration?.users
        ) {
          userOptions.value = props.configuration.users
            .filter(user => configForm.user_ids.includes(user.id))
            .map(user => ({
              value: user.id,
              label: user.name,
            }));
        } else {
          userOptions.value = [];
        }
      }
    })
    .catch(error => {
      console.error('Error fetching advisors:', error);
      // Preserve existing selected users on error
      if (
        isEdit.value &&
        configForm.user_ids?.length > 0 &&
        props.configuration?.users
      ) {
        userOptions.value = props.configuration.users
          .filter(user => configForm.user_ids.includes(user.id))
          .map(user => ({
            value: user.id,
            label: user.name,
          }));
      } else {
        userOptions.value = [];
      }
    })
    .finally(() => {
      advisorsLoading.value = false;
    });
};

// Load advisors if editing an existing configuration
if (isEdit.value && configForm.quote_type_id) {
  fetchAdvisors(configForm.quote_type_id);
}

// Watch for quote type changes to fetch advisors
watch(
  () => configForm.quote_type_id,
  newQuoteTypeId => {
    // Only reset user_ids when not in edit mode or when deliberately changing quote type
    if (
      !isEdit.value ||
      (isEdit.value && newQuoteTypeId !== props.configuration?.quote_type_id)
    ) {
      configForm.user_ids = [];
    }

    if (newQuoteTypeId) {
      fetchAdvisors(newQuoteTypeId);
    } else {
      userOptions.value = [];
    }
  },
);

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

      <x-field label="Advisors" class="sm:col-span-2" required>
        <x-select
          v-model="configForm.user_ids"
          :options="userOptions"
          :placeholder="advisorPlaceholder"
          multiple
          filterable
          :rules="[isRequired]"
          :error="configForm.errors.user_ids"
          class="w-full min-h-[40px]"
          :loading="advisorsLoading"
          :disabled="!configForm.quote_type_id || advisorsLoading"
        >
          <template #content-footer v-if="userOptions.length > 0">
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
        <x-field label="Should Skip SIC">
          <label class="flex items-center space-x-2 cursor-pointer">
            <x-toggle
              v-model="configForm.should_skip_sic"
              color="success"
              size="lg"
            />
            <span class="text-sm text-gray-600">
              {{ configForm.should_skip_sic ? 'Yes' : 'No' }}
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
