<script setup>
const emit = defineEmits(['handleSorting']);
const props = defineProps({
  options: {
    type: Object,
    require: true,
  },
});
const sortOptions = ref([
  { text: 'Created Date', value: 'created_at' },
  { text: 'Modified Date', value: 'modified_at' },
  { text: 'Price', value: 'price' },
]);

const toggle = ref(true);
const selectedOptions = ref(props.options);

const toggleFilter = () => {
  toggle.value = !toggle.value;
};

const determineSortOptionText = computed(() => {
  return sortOptions.value.find(
    option => option.value === selectedOptions.value.sortBy,
  ).text;
});

const handleSortBy = sortBy => {
  selectedOptions.value.sortBy = sortBy;
  emit('handleSorting');
};

const handleSortType = sortType => {
  selectedOptions.value.sortType = sortType;
  emit('handleSorting');
};
</script>
<template>
  <div class="flex gap-px">
    <x-button
      size="xs"
      color="#10b981"
      class="rounded-r-none rounded-l-lg"
      square
    >
      <x-icon
        v-if="selectedOptions.sortType == 'asc'"
        icon="sortAsc"
        size="md"
        class="transition transform duration-300"
        @click="handleSortType('desc')"
      />
      <x-icon
        v-else
        icon="sortDesc"
        size="md"
        class="transition transform duration-300"
        @click="handleSortType('asc')"
      />
    </x-button>
    <div class="flex">
      <x-popover align="left" position="bottom">
        <x-button size="sm" color="#10b981" class="rounded-none">
          Sort By: {{ determineSortOptionText }}
        </x-button>

        <template #content>
          <div class="w-72 bg-white shadow-lg border z-20 rounded">
            <p class="bg-gray-200 w-full text-xs p-1 font-bold">SORT BY</p>
            <div class="p-2 overflow-x-auto max-h-80">
              <ul class="space-y-0.5">
                <li
                  v-for="option in sortOptions"
                  :key="option.text"
                  :class="{
                    'bg-primary text-white':
                      selectedOptions.sortBy === option.value,
                  }"
                  class="px-3 py-1 capitalize text-sm cursor-pointer rounded-sm transition hover:bg-primary hover:text-white"
                  @click="handleSortBy(option.value)"
                >
                  {{ option.text }}
                </li>
              </ul>
            </div>
          </div>
        </template>
        <x-button
          size="xs"
          color="#10b981"
          class="rounded-l-none rounded-r-lg"
          square
          @click="toggleFilter"
        >
          <x-icon
            icon="chevronDown"
            size="sm"
            class="transition transform duration-300"
            :class="toggle ? '' : 'rotate-180'"
          />
        </x-button>
      </x-popover>
    </div>
  </div>
</template>
