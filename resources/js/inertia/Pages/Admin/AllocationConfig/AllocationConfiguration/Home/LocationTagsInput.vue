<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  label: {
    type: String,
    default: 'Locations',
  },
  placeholder: {
    type: String,
    default: 'Type location and press Enter...',
  },
  tooltip: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update:modelValue']);

const inputValue = ref('');
const inputRef = ref(null);

const tags = computed({
  get: () => props.modelValue || [],
  set: value => emit('update:modelValue', value),
});

const addTag = () => {
  const trimmedValue = inputValue.value.trim();

  if (trimmedValue && !tags.value.includes(trimmedValue)) {
    tags.value = [...tags.value, trimmedValue];
    inputValue.value = '';
  }
};

const removeTag = index => {
  if (!props.disabled) {
    const newTags = [...tags.value];
    newTags.splice(index, 1);
    tags.value = newTags;
  }
};

const handleKeyDown = event => {
  if (event.key === 'Enter') {
    event.preventDefault();
    addTag();
  } else if (event.key === ',') {
    event.preventDefault();
    addTag();
  } else if (
    event.key === 'Backspace' &&
    !inputValue.value &&
    tags.value.length > 0
  ) {
    removeTag(tags.value.length - 1);
  }
};

const focusInput = () => {
  if (!props.disabled) {
    inputRef.value?.focus();
  }
};
</script>

<template>
  <div class="location-tags-input">
    <div v-if="label" class="flex items-center mb-2">
      <label class="block text-sm font-medium text-gray-700">
        {{ label }}
      </label>
      <x-tooltip v-if="tooltip">
        <svg
          class="ml-1 h-4 w-4 text-gray-400 cursor-help"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="1.5"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"
          />
        </svg>
        <template #tooltip>
          <span class="custom-tooltip-content">{{ tooltip }}</span>
        </template>
      </x-tooltip>
    </div>

    <div
      class="tags-container min-h-[40px] w-full border border-gray-300 rounded-md px-3 py-2 flex flex-wrap gap-2 items-center cursor-text transition-colors"
      :class="{
        'bg-gray-100 cursor-not-allowed': disabled,
        'hover:border-gray-400 focus-within:border-orange-500 focus-within:ring-1 focus-within:ring-orange-500':
          !disabled,
      }"
      @click="focusInput"
    >
      <!-- Tags -->
      <div
        v-for="(tag, index) in tags"
        :key="index"
        class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 rounded-md text-sm font-medium"
      >
        <span>{{ tag }}</span>
        <button
          v-if="!disabled"
          type="button"
          @click.stop="removeTag(index)"
          class="hover:bg-orange-200 rounded-full p-0.5 transition-colors"
        >
          <svg
            class="h-3 w-3"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="2"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <!-- Input -->
      <input
        ref="inputRef"
        v-model="inputValue"
        type="text"
        :disabled="disabled"
        :placeholder="tags.length === 0 ? placeholder : ''"
        class="flex-1 min-w-[120px] outline-none bg-transparent text-sm"
        @keydown="handleKeyDown"
        @blur="addTag"
      />
    </div>

    <p v-if="!disabled" class="mt-1 text-xs text-gray-500">
      Type a location and press Enter or comma to add
    </p>
  </div>
</template>

<style scoped>
.tags-container {
  min-height: 40px;
}

.location-tags-input input::placeholder {
  color: #9ca3af;
}
</style>
