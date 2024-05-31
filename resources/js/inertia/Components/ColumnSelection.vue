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
  withoutStorage: {
    type: Boolean,
    default: false,
  },
});

const headers = ref([]);

let storedState = ref({ headers: [] });

if (!props.withoutStorage) {
  storedState = useStorage(props.storageKey, {
    headers: props.columns,
  });
}

function onChange() {
  try {
    emit(
      'update:columns',
      headers.value.filter(c => c.is_active),
    );
    if (!props.withoutStorage) {
      storedState.value.headers = headers.value;
    }
  } catch (error) {
    console.error('Error in onChange:', error);
  }
}

function onReset() {
  try {
    if (!props.withoutStorage) {
      headers.value = storedState.value.headers;
    }
    emit(
      'update:columns',
      headers.value.map(c => {
        c.is_active = true;
        return c;
      }),
    );
  } catch (error) {
    console.error('Error in onReset:', error);
  }
}

function matchColumns() {
  try {
    const columns = props.columns;
    const storedColumns = storedState.value.headers;
    if (columns.length !== storedColumns.length) {
      return false;
    }
    for (let i = 0; i < columns.length; i++) {
      if (columns[i].text !== storedColumns[i].text) {
        return false;
      }
    }
    return true;
  } catch (error) {
    console.error('Error in matchColumns:', error);
  }
}

watchEffect(() => {
  if (props.withoutStorage) {
    headers.value = props.columns;
  }
});

onMounted(() => {
  if (storedState.value?.headers?.length > 0) {
    if (!matchColumns()) {
      storedState.value.headers = props.columns;
    }
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
    <x-popover
      align="right"
      position="bottom"
      :dismissOnClick="false"
      :autoAlign="false"
    >
      <x-badge
        :show="
          headers.length > 0 &&
          headers.length !== headers.filter(c => c.is_active).length
        "
        color="red"
        align="left"
        outlined
        size="sm"
      >
        <x-button v-if="headers.length > 0" icon="settings" square ghost />
      </x-badge>
      <template #content>
        <x-popover-container>
          <div class="w-72 bg-white border rounded shadow">
            <p class="bg-gray-200 w-full text-sm p-1 font-semibold">
              CHOOSE COLUMNS
            </p>
            <div class="p-2 overflow-x-auto max-h-72">
              <header class="text-gray-500 text-xs">SHOW FIELDS</header>
              <ul class="px-2">
                <template v-if="headers.filter(c => c.is_active).length > 0">
                  <li
                    v-for="column in headers.filter(c => c.is_active)"
                    :key="column.text"
                  >
                    <x-checkbox
                      v-model="column.is_active"
                      :name="column.text"
                      size="sm"
                      class="!mb-0"
                      @update:model-value="onChange"
                    >
                      <span class="text-sm uppercase hover:text-primary-800">
                        {{ column.text }}
                      </span>
                    </x-checkbox>
                  </li>
                </template>
                <li
                  v-else
                  class="text-xs text-gray-400 italic text-center py-1.5"
                >
                  All fields are hidden
                </li>
              </ul>
              <header class="text-gray-500 text-xs pt-1">
                FIELDS IN THE LIST
              </header>
              <ul class="px-2">
                <template v-if="headers.filter(c => !c.is_active).length > 0">
                  <li
                    v-for="column in headers.filter(c => !c.is_active)"
                    :key="column.text"
                  >
                    <x-checkbox
                      v-model="column.is_active"
                      :name="column.text"
                      size="sm"
                      class="!mb-0"
                      @update:model-value="onChange"
                    >
                      <span class="text-sm uppercase hover:text-primary-800">
                        {{ column.text }}
                      </span>
                    </x-checkbox>
                  </li>
                </template>
                <li
                  v-else
                  class="text-xs text-center text-gray-400 py-1.5 italic"
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
