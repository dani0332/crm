<script setup>
const emit = defineEmits(['update:columns']);

const props = defineProps({
  columns: {
    type: Array,
    required: true,
  },
  storageKey: {
    type: String,
    required: true,
  },
});

const headers = ref(props.columns);

const storedState = useStorage(props.storageKey, {
  headers: [],
});

function onChange() {
  emit(
    'update:columns',
    headers.value.filter(c => c.is_active),
  );
  storedState.value.headers = headers.value;
}

function onReset() {
  headers.value = storedState.value.headers;
  emit(
    'update:columns',
    headers.value.map(c => {
      c.is_active = true;
      return c;
    }),
  );
}

onMounted(() => {
  if (storedState.value?.headers?.length > 0) {
    headers.value = storedState.value.headers;
    emit(
      'update:columns',
      headers.value.filter(c => c.is_active),
    );
  }
});
</script>
<template>
  <div class="select-none">
    <x-popover align="right" position="bottom" :dismissOnClick="false">
      <x-button icon="settings" square ghost />
      <template #content>
        <x-popover-container>
          <div class="w-72 bg-white border rounded shadow">
            <p class="bg-gray-200 w-full text-sm p-1 font-semibold">
              CHOOSE COLUMNS
            </p>
            <div class="p-2 overflow-x-auto max-h-72">
              <header class="uppercase text-gray-500 text-xs">
                Show Fields
              </header>
              <ul class="px-2">
                <template v-if="headers.length > 0">
                  <li
                    v-for="column in headers.filter(c => c.is_active)"
                    :key="column.text"
                  >
                    <x-checkbox
                      v-model="column.is_active"
                      size="sm"
                      @update:model-value="onChange"
                    >
                      <span class="text-sm uppercase"> {{ column.text }}</span>
                    </x-checkbox>
                  </li>
                </template>
                <li
                  v-else
                  class="text-sm text-gray-400 italic text-center py-1.5"
                >
                  No Fields Found
                </li>
              </ul>
              <header class="uppercase text-gray-500 text-xs pt-1">
                Fields in the list
              </header>
              <ul class="px-2">
                <template v-if="headers.length > 0">
                  <li
                    v-for="column in headers.filter(c => !c.is_active)"
                    :key="column.text"
                  >
                    <x-checkbox
                      v-model="column.is_active"
                      size="sm"
                      @update:model-value="onChange"
                    >
                      <span class="text-sm uppercase"> {{ column.text }}</span>
                    </x-checkbox>
                  </li>
                </template>
                <li
                  v-else
                  class="text-sm text-center text-gray-400 py-1.5 italic"
                >
                  No fields are hidden
                </li>
              </ul>
            </div>
            <div class="p-1.5 shadow-inner">
              <x-button
                size="xs"
                @click="onReset"
                :disabled="
                  headers.length === 0 ||
                  headers.length === headers.filter(c => c.is_active).length
                "
                block
              >
                Reset to Default
              </x-button>
            </div>
          </div>
        </x-popover-container>
      </template>
    </x-popover>
  </div>
</template>
