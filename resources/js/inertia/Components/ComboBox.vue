<script setup>
const props = defineProps({
  label: {
    type: String,
    default: '',
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
  searchPlaceholder: {
    type: String,
    default: 'Search options',
  },
  hasError: {
    type: Boolean,
    default: false,
  },
  maxLimit: {
    type: Number,
    default: 0,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  autocomplete: {
    type: Boolean,
    default: false,
  },
  hideFooter: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue']);

const selectedValue = computed({
  get() {
    if (props.single) return [];

    return props.options.filter(option =>
      props.modelValue?.includes(option.value),
    );
  },
  set(newValue) {
    const { single, maxLimit } = props;
    if (single) {
      emit('update:modelValue', newValue.value);
      return;
    }
    const values = newValue.map(item => item.value);

    if (maxLimit > 0 && values.length > maxLimit) {
      values.splice(0, values.length - maxLimit);
    }
    emit('update:modelValue', values);
  },
});
const query = ref('');

const filteredList = computed(() => {
  return query.value === ''
    ? props.options
    : props.options.filter(option => {
        return option.label.toLowerCase().includes(query.value.toLowerCase());
      });
});

const { list, containerProps, wrapperProps, scrollTo } = useVirtualList(
  filteredList,
  {
    itemHeight: 34,
    overscan: 10,
  },
);

const onSelectAll = () => {
  const values = props.options.map(item => item.value);
  emit('update:modelValue', values);
};

const onDeselectAll = () => {
  emit('update:modelValue', []);
};

const removeSelected = item => {
  let index = props.modelValue.findIndex(x => {
    return x == item.value || x == item.label;
  });
  if (index != -1) {
    props.modelValue.splice(index, 1);
  }
  emit(
    'update:modelValue',
    selectedValue?.value?.map(item => item.value) || [],
  );
};
</script>

<template>
  <label
    class="group relative x-select inline-block align-bottom text-left focus:outline-none mb-3 w-full"
  >
    <p v-if="props.label" class="font-medium text-gray-800 mb-1">
      {{ props.label }}
    </p>
    <Combobox
      v-model="selectedValue"
      :multiple="!props.single"
      as="div"
      class="relative"
      :disabled="props.disabled"
    >
      <div>
        <div
          class="select-none outline-transparent outline outline-2 outline-offset-[-1px] transition-all duration-150 ease-in-out border-gray-300 shadow-sm rounded-md hover:border-gray-400 px-3 py-2 bg-white text-gray-700 active:outline-primary-500 w-full flex flex-wrap gap-1 border pr-5 pl-2"
          v-if="selectedValue.length > 0 && autocomplete"
        >
          <x-tag
            v-for="item in selectedValue"
            :key="item.label"
            removable
            size="xs"
            color="primary"
            @remove="removeSelected(item)"
          >
            {{ item.label }}
          </x-tag>
        </div>
        <ComboboxInput
          v-else
          as="input"
          :displayValue="list => list?.label"
          :class="{
            'border-red-500': props.hasError,
            '!bg-gray-100': props.disabled,
          }"
          class="appearance-none block placeholder-gray-400 outline-transparent outline outline-2 outline-offset-[-1px] transition-all duration-150 ease-in-out border-gray-300 border shadow-sm rounded-md hover:border-gray-400 px-3 py-2 bg-white text-gray-700 focus:outline-primary-500 w-full"
          :placeholder="props.placeholder"
          :value="
            props.single
              ? props.options.find(option => option.value === props.modelValue)
                  ?.label
              : `${selectedValue.length} Selected ${
                  props.maxLimit ? '| max: ' + props.maxLimit : ''
                }`
          "
          readonly
        />
        <ComboboxButton
          class="absolute right-0 w-full h-full"
          :class="{
            'bottom-0': selectedValue.length == 0 || !props.autocomplete,
          }"
        />
      </div>

      <TransitionRoot
        leave="transition ease-in duration-100"
        leaveFrom="opacity-100"
        leaveTo="opacity-0"
        @after-leave="query = ''"
      >
        <ComboboxOptions
          class="absolute z-10 mt-1 max-h-64 h-auto w-full overflow-hidden rounded-md bg-white text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
        >
          <li class="pt-2 pb-1 px-2 -mb-2">
            <x-input
              size="xs"
              v-model="query"
              :placeholder="`${props.searchPlaceholder} (${props.options.length})`"
              class="w-full"
            />
          </li>

          <li
            v-if="filteredList.length === 0 && query !== ''"
            class="relative cursor-default select-none py-2 px-4 text-gray-600 text-xs"
          >
            No results found
          </li>
          <div v-bind="containerProps" class="max-h-40 overflow-auto h-full">
            <div v-bind="wrapperProps">
              <ComboboxOption
                as="template"
                v-for="{ data: item } of list"
                v-slot="{ selected }"
                :key="`${item.value}-${item.label}`"
                :value="item"
              >
                <li
                  :class="{
                    'text-primary': props.single
                      ? props.modelValue == item.value
                      : selected,
                  }"
                  class="relative flex items-center whitespace-nowrap px-3 text-sm cursor-pointer py-1.5 hover:bg-primary-50"
                  :title="item.tooltip"
                >
                  <span class="flex-1 truncate py-px">
                    {{ item.label }}
                  </span>
                  <span class="ml-1 shrink-0">
                    <svg
                      v-if="
                        props.single ? props.modelValue == item.value : selected
                      "
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
            </div>
          </div>
          <div class="p-2" v-if="!props.single">
            <div class="flex flex-row justify-between gap-2">
              <x-button
                v-if="!props.maxLimit && props.options.length > 0"
                size="xs"
                color="primary"
                light
                @click="onSelectAll"
              >
                Select All
              </x-button>
              <x-button
                v-if="props.modelValue.length > 0"
                size="xs"
                color="error"
                light
                @click="onDeselectAll"
              >
                Deselect All
              </x-button>
            </div>
          </div>
        </ComboboxOptions>
      </TransitionRoot>
      <div
        class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2"
      >
        <x-spinner v-if="props.loading" size="sm" class="text-primary" />
        <svg
          v-else
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
    <div v-if="!props.hideFooter" class="x-input-footer text-xs mt-1">
      <p v-if="props.hasError" class="text-error-500 dark:text-error-400">
        This field is required
      </p>
    </div>
  </label>
</template>
