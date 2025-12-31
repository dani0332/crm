<script setup>
import { ref, computed, watch, toRef } from 'vue';
import { mockServerItems } from '../../Composables/ServerDatatable';

const props = defineProps({
  propHead: {
    type: Array,
    default: [],
  },
  rowItems: {
    type: Array,
    default: [25, 50, 100],
  },
  clickedRowEvent: {
    type: Function,
    default: () => {},
  },
  url: {
    type: String,
    default: '',
  },
  multiSort: {
    type: Boolean,
    default: false,
  },
  search: {
    type: Boolean,
    default: false,
  },
  module: {
    type: String,
    default: '',
  },
  selection: {
    type: Boolean,
    default: false,
  },
  selectOption: {
    type: Boolean,
    default: false,
  },
  additionalQuery: {
    type: Object,
    default: {},
  },
  currentSelectedItem: {
    type: Object,
    default: null,
  },
});

const searchField = ref('player');
const searchValue = ref('');
const loading = ref(false);
const sortBy = ref([]);
const sortType = ['desc', 'asc'];
const headers = ref(props.propHead);
const reactiveAdditionalQuery = toRef(props, 'additionalQuery');

const items = ref([]);

const serverItemsLength = ref(0);
const serverOptions = ref({
  page: 1,
  rowsPerPage: props.rowItems[0],
});

const clickRow = item => {
  props.clickedRowEvent(item);
};

const requestUrl = computed(() => {
  const { page, rowsPerPage, sortBy, sortType } = serverOptions.value;
  const searchText = searchValue.value;
  const additionalQueryText =
    Object.keys(reactiveAdditionalQuery.value).length > 0
      ? Object.keys(reactiveAdditionalQuery.value)
          .map(function (key) {
            return key + '=' + reactiveAdditionalQuery.value[key];
          })
          .join('&')
      : '';
  var queryString =
    additionalQueryText != ''
      ? route(props.url) + '?' + additionalQueryText + '&'
      : route(props.url) + '?';
  if (sortBy && sortType) {
    return searchText != '' && searchText != null
      ? queryString +
          `search=${searchText}&page=${page}&limit=${rowsPerPage}&sortBy=${sortBy}&sortType=${sortType}`
      : queryString +
          `page=${page}&limit=${rowsPerPage}&sortBy=${sortBy}&sortType=${sortType}`;
  } else {
    return searchText != '' && searchText != null
      ? queryString + `search=${searchText}&page=${page}&limit=${rowsPerPage}`
      : queryString + `page=${page}&limit=${rowsPerPage}`;
  }
});

const loadFromServer = async () => {
  loading.value = true;
  const { serverCurrentPageItems, serverTotalItemsLength } =
    await mockServerItems(serverOptions.value, requestUrl.value);
  items.value = serverCurrentPageItems;
  serverItemsLength.value = serverTotalItemsLength;
  loading.value = false;
};
const bodyRowClassNameFunction = (item, rowNumber) => {
  if (
    props.currentSelectedItem != null &&
    item.id == props.currentSelectedItem.id
  ) {
    return 'pass-row';
  }
  return 'fail-row';
};

// first load when created
loadFromServer();

watch(
  [serverOptions, reactiveAdditionalQuery],
  value => {
    loadFromServer();
  },
  { deep: true },
);
</script>

<template>
  <form>
    <div class="flex w-full justify-start gap-2 py-2">
      <div class="" v-if="search">
        <x-input
          type="text"
          name="search"
          id="searchField"
          @change="() => loadFromServer()"
          placeholder="Search"
          v-model="searchValue"
        />
      </div>
      <div v-if="selectOption"></div>
    </div>
  </form>

  <DataTable
    v-model:server-options="serverOptions"
    :server-items-length="serverItemsLength"
    :rows-items="rowItems"
    :sort-by="sortBy"
    :sort-type="sortType"
    :loading="loading"
    :headers="headers"
    :items="items"
    @click-row="clickRow"
    :multi-sort="multiSort"
  >
    <template #item-action="item">
      <slot name="action" v-bind="item"></slot>
    </template>
  </DataTable>
</template>
