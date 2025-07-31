<script setup>
import {
  getReadableFieldName,
  shouldShowFieldName,
} from '../utils/fieldNameFormatter.js';

const props = defineProps({
  hasErrors: {
    type: Boolean,
    default: false,
  },
  errorTitle: {
    type: String,
    default: 'Error',
  },
  hasValidationErrors: {
    type: Boolean,
    default: false,
  },
  groupedErrors: {
    type: Object,
    default: () => ({}),
  },
  allErrors: {
    type: Array,
    default: () => [],
  },
  getErrorIcon: {
    type: Function,
    required: true,
  },
  getErrorTitle: {
    type: Function,
    required: true,
  },
});
</script>

<template>
  <!-- Unified Error Display Section -->
  <div
    v-if="hasErrors"
    class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4 mt-4"
  >
    <div class="flex items-start">
      <div class="flex-shrink-0">
        <svg
          class="h-5 w-5 text-red-400"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <path
            fill-rule="evenodd"
            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
            clip-rule="evenodd"
          />
        </svg>
      </div>
      <div class="ml-3 flex-1">
        <h3 class="text-sm font-medium text-red-800 mb-2">
          {{ errorTitle }}
        </h3>

        <!-- Unified grouped error display for all error types -->
        <div class="space-y-3">
          <div
            v-for="(errorGroup, type) in groupedErrors"
            :key="type"
            class="border-l-4 border-red-300 pl-3"
          >
            <div class="flex items-center mb-1">
              <span class="text-lg mr-2">{{ getErrorIcon(type) }}</span>
              <h4 class="text-sm font-medium text-red-700">
                {{ getErrorTitle(type) }}
              </h4>
            </div>
            <ul
              class="list-disc list-inside space-y-1 text-sm text-red-600 ml-6"
            >
              <li
                v-for="error in errorGroup"
                :key="`${error.field}-${error.message}`"
                class="leading-relaxed"
              >
                {{ error.message }}
                <span
                  v-if="error.field && shouldShowFieldName(error.field)"
                  class="text-xs text-red-500 ml-1"
                >
                  ({{ getReadableFieldName(error.field) }})
                </span>
              </li>
            </ul>
          </div>

          <!-- Show helpful tip for validation-related errors -->
          <div
            v-if="hasValidationErrors"
            class="mt-3 pt-2 border-t border-red-200"
          >
            <p class="text-xs text-red-600">
              💡 Tip: Make sure all amount ranges don't overlap, each profile
              has advisors and nationalities selected, and all required fields
              are filled.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
