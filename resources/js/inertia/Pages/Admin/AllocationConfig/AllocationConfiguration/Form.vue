<script setup>
import { onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';

import SavingsAllocationConfigTemplate from './Savings/SavingsAllocationConfigTemplate.vue';
import HomeAllocationConfigTemplate from './Home/HomeAllocationConfigTemplate.vue';
import LifeAllocationConfigTemplate from './Life/LifeAllocationConfigTemplate.vue';
import SimpleAllocationConfigTemplate from './Simple/SimpleAllocationConfigTemplate.vue';
import CorplineAllocationConfigTemplate from './Corpline/CorplineAllocationConfigTemplate.vue';
import GroupMedicalAllocationConfigTemplate from './GroupMedical/GroupMedicalAllocationConfigTemplate.vue';
import ErrorDisplay from './components/ErrorDisplay.vue';
import { useErrorHandling } from './composables/useErrorHandling.js';
import { useAllocationForm } from './composables/useAllocationForm.js';

const props = defineProps({
  quoteTypes: Array,
  nationalities: Array,
  quoteTypeCodeEnum: Object,
});

const { isRequired } = useRules();

// Initialize error handling
const errorHandling = useErrorHandling();
const {
  hasErrors,
  errorTitle,
  hasValidationErrors,
  groupedErrors,
  allErrors,
  getErrorIcon,
  getErrorTitle,
} = errorHandling;

// Initialize form logic
const formLogic = useAllocationForm(props, errorHandling);
const {
  isQuoteTypeLoading,
  isSubmitting,
  successMessage,
  templateData,
  advisorOptions,
  nationalityOptions,
  teamOptions,
  planTypeOptions,
  currentConfiguration,
  savingsTemplateRef,
  homeTemplateRef,
  lifeTemplateRef,
  simpleTemplateRef,
  corplineTemplateRef,
  groupMedicalTemplateRef,
  auditLogsKey,
  form,
  quoteTypeOptions,
  isViewMode,
  initializeOptions,
  onTemplateDataUpdate,
  onSubmit,
  enableEditMode,
  cancelEdit,
} = formLogic;

onMounted(() => {
  initializeOptions();
});
</script>

<template>
  <Head title="Allocation Configuration" />

  <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-6">
    Config ILA for non-motor and health LOBs
  </h2>

  <div class="mx-auto sm:px-6 lg:px-8">
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
          <div class="max-w-md">
            <x-field label="Line of business" required>
              <x-select
                v-model="form.selectedQuoteTypeCode"
                :options="quoteTypeOptions"
                placeholder="Select line of business"
                filterable
                :rules="[isRequired]"
                :error="form.errors.quote_type"
              />
            </x-field>

            <div
              v-if="!form.selectedQuoteTypeCode && !isQuoteTypeLoading"
              class="mt-2 text-sm text-gray-500"
            >
              Please select a line of business to begin configuration
            </div>

            <div
              v-if="form.selectedQuoteTypeCode && !isQuoteTypeLoading"
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

      <ErrorDisplay
        :has-errors="hasErrors"
        :error-title="errorTitle"
        :has-validation-errors="hasValidationErrors"
        :grouped-errors="groupedErrors"
        :all-errors="allErrors"
        :get-error-icon="getErrorIcon"
        :get-error-title="getErrorTitle"
      />

      <!-- Success Message -->
      <div
        v-if="successMessage"
        class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4 mt-4"
      >
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <svg
              class="h-5 w-5 text-green-400"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clip-rule="evenodd"
              />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-green-800">
              {{ successMessage }}
            </p>
          </div>
        </div>
      </div>

      <div class="mt-4" v-if="form.quote_type && !isQuoteTypeLoading">
        <div v-if="form.quote_type === quoteTypeCodeEnum.SAVINGS">
          <SavingsAllocationConfigTemplate
            :configuration="currentConfiguration"
            :advisor-options="advisorOptions"
            :nationality-options="nationalityOptions"
            :view-mode="isViewMode"
            @data-update="onTemplateDataUpdate"
            ref="savingsTemplateRef"
          />
        </div>

        <div v-else-if="form.quote_type === quoteTypeCodeEnum.HOME">
          <HomeAllocationConfigTemplate
            :configuration="currentConfiguration"
            :advisor-options="advisorOptions"
            :nationality-options="nationalityOptions"
            :view-mode="isViewMode"
            @data-update="onTemplateDataUpdate"
            ref="homeTemplateRef"
          />
        </div>

        <div v-else-if="form.quote_type === quoteTypeCodeEnum.LIFE">
          <LifeAllocationConfigTemplate
            :configuration="currentConfiguration"
            :advisor-options="advisorOptions"
            :nationality-options="nationalityOptions"
            :view-mode="isViewMode"
            @data-update="onTemplateDataUpdate"
            ref="lifeTemplateRef"
          />
        </div>

        <div
          v-else-if="
            form.quote_type === quoteTypeCodeEnum.PET ||
            form.quote_type === quoteTypeCodeEnum.YACHT ||
            form.quote_type === quoteTypeCodeEnum.CYCLE
          "
        >
          <SimpleAllocationConfigTemplate
            :configuration="currentConfiguration"
            :advisor-options="advisorOptions"
            :nationality-options="nationalityOptions"
            :view-mode="isViewMode"
            :lob-name="form.quote_type"
            @data-update="onTemplateDataUpdate"
            ref="simpleTemplateRef"
          />
        </div>

        <div v-else-if="form.quote_type === quoteTypeCodeEnum.CORPLINE">
          <CorplineAllocationConfigTemplate
            :configuration="currentConfiguration"
            :advisor-options="advisorOptions"
            :team-options="teamOptions"
            :view-mode="isViewMode"
            @data-update="onTemplateDataUpdate"
            ref="corplineTemplateRef"
          />
        </div>

        <div v-else-if="form.quote_type === quoteTypeCodeEnum.GROUP_MEDICAL">
          <GroupMedicalAllocationConfigTemplate
            :configuration="currentConfiguration"
            :advisor-options="advisorOptions"
            :plan-type-options="planTypeOptions"
            :view-mode="isViewMode"
            @data-update="onTemplateDataUpdate"
            ref="groupMedicalTemplateRef"
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
        v-if="form.quote_type && !isQuoteTypeLoading"
        class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-4"
      >
        <div class="p-6 bg-white border-b border-gray-200">
          <!-- View Mode Buttons -->
          <div
            v-if="isViewMode && currentConfiguration"
            class="flex justify-end gap-3 mb-4"
          >
            <x-tooltip>
              <x-button
                size="md"
                color="#ff5e00"
                type="button"
                @click="enableEditMode"
              >
                Edit
              </x-button>
              <template #tooltip>
                <span class="custom-tooltip-content">
                  Click to edit the {{ form.quote_type }} configuration.
                </span>
              </template>
            </x-tooltip>
          </div>

          <div v-if="!isViewMode" class="flex justify-end gap-3 mb-4">
            <x-tooltip v-if="currentConfiguration">
              <x-button
                size="md"
                color="secondary"
                type="button"
                outlined
                @click="cancelEdit"
              >
                Cancel
              </x-button>
              <template #tooltip>
                <span class="custom-tooltip-content">
                  Cancel editing and return to view mode without saving changes.
                </span>
              </template>
            </x-tooltip>

            <x-tooltip>
              <x-button
                size="md"
                color="emerald"
                type="submit"
                :loading="isSubmitting"
              >
                {{ currentConfiguration ? 'Update' : 'Save' }}
                {{ form.quote_type }} Configuration
              </x-button>
              <template #tooltip>
                <span class="custom-tooltip-content">
                  Click to {{ currentConfiguration ? 'update' : 'save' }} and
                  apply all configuration changes.
                </span>
              </template>
            </x-tooltip>
          </div>
        </div>
      </div>
    </x-form>

    <AuditLogs
      v-if="currentConfiguration && form.quote_type"
      :key="`audit-logs-${auditLogsKey}`"
      :url="'\\auditable'"
      :type="'App\\Models\\Allocation\\AllocationConfiguration'"
      :id="currentConfiguration.id"
    />
  </div>
</template>
