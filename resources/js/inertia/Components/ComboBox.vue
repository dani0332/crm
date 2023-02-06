<script setup>
import { computed, ref } from 'vue';
import {
  Combobox,
  ComboboxInput,
  ComboboxButton,
  ComboboxOptions,
  ComboboxOption,
  TransitionRoot,
} from '@headlessui/vue';

const props = defineProps({
  label: {
    required: true,
    type: String,
  },
  options: {
    type: Array,
    default: [],
  },
  modelValue: {
    type: [String, Array, Number],
    default: [],
  },
  single: {
    type: Boolean,
    default: false,
  },
  placeholder: {
    type: String,
    default: 'Select an option',
  },
  hasError: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue']);

const selectedValue = computed({
  get() {
    if (props.single) return [];

    return props.options.filter(option =>
      props.modelValue.includes(option.value),
    );
  },
  set(newValue) {
    if (props.single) {
      emit('update:modelValue', newValue.value);
      return;
    }
    const values = newValue.map(item => item.value);
    emit('update:modelValue', values);
  },
});
const query = ref('');

const filteredList = computed(() =>
  query.value === ''
    ? props.options
    : props.options.filter(option => {
        return option.label.toLowerCase().includes(query.value.toLowerCase());
      }),
);
</script>

<template>
  <label
    class="group relative x-select inline-block align-bottom text-left focus:outline-none mb-3 w-full"
  >
    <p class="font-medium text-gray-800 mb-1">
      {{ props.label }}
    </p>
    <Combobox
      v-model="selectedValue"
      :multiple="!props.single"
      as="div"
      class="relative"
    >
      <ComboboxInput
        :displayValue="list => list?.label"
        :class="{
          'border-red-500': props.hasError,
        }"
        class="appearance-none block placeholder-gray-400 outline-transparent outline outline-2 outline-offset-[-1px] transition-all duration-150 ease-in-out border-gray-300 border shadow-sm rounded-md hover:border-gray-400 px-3 py-2 bg-white text-gray-700 focus:outline-sky-500 w-full"
        :placeholder="props.placeholder"
        :value="
          props.single
            ? props.options.find(option => option.value === props.modelValue)
                ?.label
            : `${selectedValue.length} Selected`
        "
        readonly
      />
      <ComboboxButton class="absolute bottom-0 right-0 w-full h-full" />
      <TransitionRoot
        leave="transition ease-in duration-100"
        leaveFrom="opacity-100"
        leaveTo="opacity-0"
        @after-leave="query = ''"
      >
        <ComboboxOptions
          class="absolute z-10 mt-1 max-h-48 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
        >
          <li class="pt-1 px-2 -mb-2">
            <x-input
              size="xs"
              v-model="query"
              placeholder="Search Options"
              class="w-full"
            />
          </li>

          <li
            v-if="filteredList.length === 0 && query !== ''"
            class="relative cursor-default select-none py-2 px-4 text-gray-600 text-xs"
          >
            No results found
          </li>
          <ComboboxOption
            as="template"
            v-slot="{ selected }"
            v-for="list in filteredList"
            :key="list.label"
            :value="list"
          >
            <li
              :class="{ 'text-primary': selected }"
              class="relative flex items-center whitespace-nowrap px-2 text-sm cursor-pointer py-1 border-b last:border-b-0 hover:bg-primary-50"
            >
              <span class="flex-1 truncate py-px">{{ list.label }}</span>
              <span class="ml-1 shrink-0">
                <svg
                  v-if="selected"
                  xmlns="http://www.w3.org/2000/svg"
                  class="shrink-0 inline h-5 w-5 stroke-2"
                  stroke-linejoin="round"
                  stroke-linecap="round"
                  stroke="currentColor"
                  fill="none"
                  viewBox="0 0 24 24"
                  data-v-27199701=""
                >
                  <path d="M5 13l4 4L19 7"></path>
                </svg>
              </span>
            </li>
          </ComboboxOption>
        </ComboboxOptions>
      </TransitionRoot>
      <div
        class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="shrink-0 x-icon inline h-5 w-5 stroke-2 text-gray-500"
          stroke-linejoin="round"
          stroke-linecap="round"
          stroke="currentColor"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
        </svg>
      </div>
    </Combobox>
    <p v-if="props.hasError" class="text-sm text-red-500 mt-1">
      This field is required
    </p>
  </label>
</template>
