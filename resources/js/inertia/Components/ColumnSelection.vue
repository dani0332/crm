<script setup>
const emit = defineEmits(['update:columns']);

const props = defineProps({
  columns: {
    type: Array,
    required: true,
  },
});

const headers = ref([...props.columns]);
const columnShown = computed(() => {
  return headers.value.filter(column => column.is_active);
});

const inactiveColumns = computed(() => {
  return headers.value.filter(column => !column.is_active);
});

const showList = ref(false);
const list = ref(null);

onClickOutside(list, event => {
  if (showList.value) showList.value = false;
});

const updateColumns = () => {
  emit(
    'update:columns',
    headers.value.filter(x => x.is_active),
  );
};
</script>
<template>
  <div class="inline relative" ref="list" id="list">
    <x-icon
      icon="settings"
      class="relative cursor-pointer"
      @click="showList = !showList"
    >
    </x-icon>
    <div
      v-if="showList"
      class="w-80 bg-white border absolute z-40 mt-1 right-1 rounded"
    >
      <p class="bg-gray-200 w-full text-xs p-1 font-semibold">CHOOSE COLUMNS</p>
      <div class="p-2 overflow-x-auto max-h-80">
        <header class="uppercase text-gray-400 text-sm">Show Fields</header>
        <ul class="px-2">
          <template v-if="columnShown.length > 0">
            <li
              class="capitalize text-xs my-1"
              v-for="column in columnShown"
              :key="column.text"
            >
              <x-checkbox
                @change="updateColumns"
                size="sm"
                v-model="column.is_active"
                >{{ column.text }}
              </x-checkbox>
            </li>
          </template>
          <li v-else class="font-bold text-sm text-center py-3">
            No Fields Found
          </li>
        </ul>
        <header class="uppercase text-gray-400 text-sm">
          Fields in the list
        </header>
        <ul class="px-2">
          <li
            class="capitalize text-xs my-1"
            v-for="column in inactiveColumns"
            :key="column.text"
          >
            <x-checkbox
              @change="updateColumns"
              size="sm"
              v-model="column.is_active"
              >{{ column.text }}
            </x-checkbox>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>