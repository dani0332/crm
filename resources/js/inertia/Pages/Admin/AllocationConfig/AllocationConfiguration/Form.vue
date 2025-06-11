<template>
  <Head title="Allocation Configuration" />

  <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-6">
    Allocation Configuration
  </h2>

  <div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <x-form @submit="onSubmit" :auto-focus="false">
        <!-- Quote Type Selection -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
              Select Quote Type
            </h3>

            <div class="max-w-md">
              <x-field label="Quote Type" required>
                <x-select
                  v-model="selectedQuoteType"
                  :options="quoteTypeOptions"
                  placeholder="Select Quote Type"
                  filterable
                  :rules="[isRequired]"
                  :error="form.errors.quote_type"
                  @change="onQuoteTypeChange"
                />
              </x-field>

              <div v-if="selectedQuoteType" class="mt-2 text-sm text-gray-600">
                Quote Type ID: {{ getQuoteTypeId(selectedQuoteType) }}
              </div>
            </div>
          </div>
        </div>

        <!-- Template Rendering Based on Quote Type -->
        <div v-if="selectedQuoteType">
          <!-- Savings Template -->
          <div v-if="selectedQuoteType === 'Savings'">
            <SavingsAllocationConfigTemplate
              v-model:savingsBrackets="form.savings_brackets"
              :advisor-options="advisorOptions"
              :nationality-options="nationalityOptions"
            />
          </div>

          <!-- Default Template for Other Quote Types -->
          <div v-else>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
              <h3 class="text-lg font-medium text-yellow-900 mb-2">
                {{ selectedQuoteType }} Configuration
              </h3>
              <p class="text-sm text-yellow-700">
                Configuration template for {{ selectedQuoteType }} is coming
                soon. This will be customized based on the specific requirements
                for {{ selectedQuoteType }} products.
              </p>
            </div>
          </div>
        </div>

        <!-- Success/Error Messages -->
        <div
          v-if="$page.props.flash.success"
          class="bg-green-50 border border-green-200 rounded-lg p-4"
        >
          <p class="text-sm text-green-700">{{ $page.props.flash.success }}</p>
        </div>

        <div
          v-if="$page.props.flash.error"
          class="bg-red-50 border border-red-200 rounded-lg p-4"
        >
          <p class="text-sm text-red-700">{{ $page.props.flash.error }}</p>
        </div>

        <!-- Form Actions -->
        <div
          v-if="selectedQuoteType"
          class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
        >
          <div class="p-6 bg-white border-b border-gray-200">
            <x-divider class="my-4" />
            <div class="flex justify-end gap-3 mb-4">
              <x-button
                size="md"
                color="emerald"
                type="submit"
                :loading="form.processing"
              >
                {{ props.configuration ? 'Update' : 'Save' }}
                {{ selectedQuoteType }} Configuration
              </x-button>
            </div>
          </div>
        </div>
      </x-form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import SavingsAllocationConfigTemplate from './SavingsAllocationConfigTemplate.vue';

const props = defineProps({
  configuration: Object,
  quoteTypes: Array,
  advisors: Array,
  nationalities: Array,
  selectedQuoteType: String,
});

const { isRequired } = useRules();

// Initialize selected quote type
const selectedQuoteType = ref(
  props.selectedQuoteType || props.configuration?.quote_type || '',
);

// Initialize form with configuration data or defaults
const getInitialFormData = () => {
  if (props.configuration) {
    return {
      quote_type_id: props.configuration.quote_type_id,
      quote_type: props.configuration.quote_type,
      savings_brackets: props.configuration.savings_brackets || [],
      // Add other quote type specific data as needed
    };
  }

  return {
    quote_type_id: '',
    quote_type: '',
    savings_brackets: [],
  };
};

const form = useForm(getInitialFormData());

// Transform quote types for x-select component
const quoteTypeOptions = computed(() => {
  return props.quoteTypes.map(quoteType => ({
    value: quoteType.name,
    label: quoteType.name,
  }));
});

// Transform advisors and nationalities for x-select component
const advisorOptions = computed(() => {
  return props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const nationalityOptions = computed(() => {
  return props.nationalities.map(nationality => ({
    value: nationality.id,
    label: nationality.text,
  }));
});

const getQuoteTypeId = quoteTypeName => {
  const quoteType = props.quoteTypes.find(qt => qt.name === quoteTypeName);
  return quoteType ? quoteType.id : '';
};

const onQuoteTypeChange = () => {
  if (selectedQuoteType.value) {
    form.quote_type = selectedQuoteType.value;
    form.quote_type_id = getQuoteTypeId(selectedQuoteType.value);

    // Reset form data when quote type changes
    if (selectedQuoteType.value === 'Savings') {
      form.savings_brackets = [];
    }

    // Update URL to include quote type parameter
    window.history.replaceState(
      {},
      '',
      route('admin.allocation-configuration.index', {
        quote_type: selectedQuoteType.value,
      }),
    );
  }
};

// Watch for changes in selected quote type to load existing configuration
watch(selectedQuoteType, newQuoteType => {
  if (newQuoteType && !props.configuration) {
    // Load existing configuration for this quote type if it exists
    const url = route('admin.allocation-configuration.index', {
      quote_type: newQuoteType,
    });
    window.location.href = url;
  }
});

function onSubmit(isValid) {
  if (isValid) {
    form.processing = true;

    if (props.configuration) {
      form.submit(
        'put',
        route('admin.allocation-configuration.update', props.configuration.id),
        {
          onError: errors => {
            Object.keys(errors).forEach(function (key) {
              form.setError(key, errors[key]);
            });
            form.processing = false;
            return false;
          },
          onSuccess: () => {
            form.processing = false;
          },
        },
      );
    } else {
      form.submit('post', route('admin.allocation-configuration.store'), {
        onError: errors => {
          Object.keys(errors).forEach(function (key) {
            form.setError(key, errors[key]);
          });
          form.processing = false;
          return false;
        },
        onSuccess: () => {
          form.processing = false;
        },
      });
    }
  }
}
</script>
