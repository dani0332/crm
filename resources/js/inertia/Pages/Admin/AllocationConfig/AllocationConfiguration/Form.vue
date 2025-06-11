<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';

import SavingsAllocationConfigTemplate from './Savings/SavingsAllocationConfigTemplate.vue';

const props = defineProps({
  quoteTypes: Array,
  nationalities: Array,
  quoteTypeCodeEnum: Object,
});

const { isRequired } = useRules();

const isQuoteTypeLoading = ref(false);
const isSubmitting = ref(false);

const successMessage = ref('');
const errorMessage = ref('');

const templateData = ref({});

const advisorOptions = ref([]);
const nationalityOptions = ref([]);
const currentConfiguration = ref(null);

const savingsTemplateRef = ref(null);

const getInitialFormData = () => {
  return {
    quote_type: '',
    quote_type_id: '',
  };
};

const form = useForm(getInitialFormData());

const quoteTypeOptions = computed(() => {
  return props.quoteTypes.map(type => ({
    value: type.id,
    label: type.text,
  }));
});

const initializeOptions = () => {
  nationalityOptions.value = props.nationalities.map(nationality => ({
    value: nationality.id,
    label: nationality.text,
  }));
};

const getQuoteType = quoteTypeId => {
  return props.quoteTypes.find(type => type.id === quoteTypeId);
};

const fetchAdvisors = async quoteType => {
  advisorOptions.value = [];
  try {
    const response = await axios.post('/advisors/by-quote-type', {
      quote_type: quoteType.code,
    });

    if (
      response.data.success &&
      response.data.data &&
      response.data.data.length > 0
    ) {
      advisorOptions.value = response.data.data.map(advisor => ({
        value: advisor.id,
        label: advisor.name,
      }));
    }
  } catch (error) {
    console.error('Error fetching advisors:', error);
  }
};

const fetchConfiguration = async quoteType => {
  currentConfiguration.value = null;
  try {
    const response = await axios.post(
      route('admin.allocation-configuration.fetch'),
      {
        quote_type: quoteType.code,
      },
    );

    if (response.data.success && response.data.data) {
      currentConfiguration.value = response.data.data;
    }
  } catch (error) {
    console.error('Error fetching configuration:', error);
  }
};

const handleQuoteTypeChange = async quoteType => {
  form.quote_type = quoteType.code;
  form.quote_type_id = quoteType.id;

  templateData.value = {};
  advisorOptions.value = [];
  currentConfiguration.value = null;

  isQuoteTypeLoading.value = true;

  try {
    await Promise.all([
      fetchAdvisors(quoteType),
      fetchConfiguration(quoteType),
    ]);

    await new Promise(resolve => setTimeout(resolve, 300));
  } catch (error) {
    console.error('Error handling quote type change:', error);
  } finally {
    isQuoteTypeLoading.value = false;
  }
};

watch(
  () => form.quote_type_id,
  (newQuoteTypeId, oldQuoteTypeId) => {
    if (newQuoteTypeId && newQuoteTypeId !== oldQuoteTypeId) {
      const newQuoteType = getQuoteType(newQuoteTypeId);

      if (!newQuoteType) {
        return;
      }

      handleQuoteTypeChange(newQuoteType);
    }
  },
);

const onTemplateDataUpdate = data => {
  templateData.value = data;
};

initializeOptions();

async function onSubmit(isValid) {
  if (isValid) {
    if (
      form.quote_type === props.quoteTypeCodeEnum.SAVINGS &&
      savingsTemplateRef.value
    ) {
      if (advisorOptions.value.length === 0) {
        errorMessage.value =
          'No advisors available for this quote type. Please ensure advisors are configured.';
        isSubmitting.value = false;
        return;
      }

      const templateValidation = savingsTemplateRef.value.validate();

      if (!templateValidation.isValid) {
        errorMessage.value = 'Please correct the errors above.';
        isSubmitting.value = false;
        return;
      }
    }

    isSubmitting.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    const submitData = {
      ...form.data(),
      ...templateData.value,
    };

    try {
      let response;

      if (currentConfiguration.value) {
        // Update existing configuration
        response = await axios.put(
          route(
            'admin.allocation-configuration.update',
            currentConfiguration.value.id,
          ),
          submitData,
        );
      } else {
        // Create new configuration
        response = await axios.post(
          route('admin.allocation-configuration.store'),
          submitData,
        );
      }

      if (response.data.success) {
        successMessage.value = response.data.message;
        currentConfiguration.value = response.data.data;
        form.clearErrors();

        // Clear template validation errors on success
        if (savingsTemplateRef.value) {
          savingsTemplateRef.value.clearValidationErrors();
        }
      } else {
        errorMessage.value =
          response.data.message ||
          'An error occurred while saving the configuration.';
      }
    } catch (error) {
      console.error('Error submitting form:', error);

      if (error.response && error.response.status === 422) {
        const validationErrors = error.response.data.errors || {};
        Object.keys(validationErrors).forEach(key => {
          form.setError(key, validationErrors[key][0]);
        });
        errorMessage.value =
          'Please correct the validation errors and try again.';
      } else if (error.response && error.response.data.message) {
        errorMessage.value = error.response.data.message;
      } else {
        errorMessage.value = 'An unexpected error occurred. Please try again.';
      }
    } finally {
      isSubmitting.value = false;
    }
  }
}
</script>

<template>
  <Head title="Allocation Configuration" />

  <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-6">
    Allocation Configuration
  </h2>

  <div class="mx-auto sm:px-6 lg:px-8">
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
          <div class="max-w-md">
            <x-field label="Quote Type" required>
              <x-select
                v-model="form.quote_type_id"
                :options="quoteTypeOptions"
                placeholder="Select Quote Type"
                filterable
                :rules="[isRequired]"
                :error="form.errors.quote_type"
              />
            </x-field>

            <div
              v-if="!form.quote_type_id && !isQuoteTypeLoading"
              class="mt-2 text-sm text-gray-500"
            >
              Please select a quote type to begin configuration
            </div>

            <div
              v-if="form.quote_type_id && !isQuoteTypeLoading"
              class="mt-2 text-sm text-gray-600"
            >
              <div class="flex items-center space-x-2">
                <span class="text-green-600">•</span>
                <span v-if="advisorOptions.length > 0"
                  >{{ advisorOptions.length }} advisors found</span
                >
                <span v-else class="text-gray-500">No advisors found</span>
              </div>
            </div>

            <!-- Loading state -->
            <div
              v-if="isQuoteTypeLoading"
              class="mt-2 text-sm text-blue-600 flex items-center"
            >
              <svg
                class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
              Fetching {{ form.quote_type }} advisors and configuration...
            </div>
          </div>
        </div>
      </div>

      <div class="mt-4" v-if="form.quote_type && !isQuoteTypeLoading">
        <div v-if="form.quote_type === quoteTypeCodeEnum.SAVINGS">
          <SavingsAllocationConfigTemplate
            :configuration="currentConfiguration"
            :advisor-options="advisorOptions"
            :nationality-options="nationalityOptions"
            @data-update="onTemplateDataUpdate"
            ref="savingsTemplateRef"
          />
        </div>

        <div v-else>
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <h3 class="text-lg font-medium text-yellow-900 mb-2">
              {{ form.quote_type }} Configuration
            </h3>
            <p class="text-sm text-yellow-700">
              Configuration template for {{ form.quote_type }} is coming soon.
            </p>
          </div>
        </div>
      </div>

      <div
        v-if="form.quote_type && isQuoteTypeLoading"
        class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-4"
      >
        <div class="p-6 bg-white border-b border-gray-200">
          <div class="flex items-center justify-center py-8">
            <div class="text-center">
              <svg
                class="animate-spin mx-auto h-8 w-8 text-blue-600"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
              <p class="mt-2 text-sm text-gray-600">
                Loading {{ form.quote_type }} Configurations...
              </p>
            </div>
          </div>
        </div>
      </div>

      <div
        v-if="successMessage"
        class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4 mt-4"
      >
        <p class="text-sm text-green-700">{{ successMessage }}</p>
      </div>

      <div
        v-if="errorMessage"
        class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4 mt-4"
      >
        <p class="text-sm text-red-700">{{ errorMessage }}</p>
      </div>

      <div
        v-if="form.quote_type && !isQuoteTypeLoading"
        class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-4"
      >
        <div class="p-6 bg-white border-b border-gray-200">
          <div class="flex justify-end gap-3 mb-4">
            <x-button
              size="md"
              color="emerald"
              type="submit"
              :loading="isSubmitting"
            >
              {{ currentConfiguration ? 'Update' : 'Save' }}
              {{ form.quote_type }} Configuration
            </x-button>
          </div>
        </div>
      </div>
    </x-form>
  </div>
</template>
