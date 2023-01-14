<script>
import {
  defineComponent,
  ref,
  reactive,
  computed,
  watch,
  onBeforeUpdate,
  nextTick,
  onMounted,
} from 'vue';
import { useUrlSearchParams } from '@vueuse/core';

export default defineComponent({
  emits: [
    'return-checked-rows',
    'do-search',
    'is-finished',
    'get-now-page',
    'row-clicked',
  ],
  props: {
    isLoading: {
      type: Boolean,
      require: true,
    },
    isReSearch: {
      type: Boolean,
      require: true,
    },
    hasCheckbox: {
      type: Boolean,
      default: false,
    },
    checkedReturnType: {
      type: String,
      default: 'key',
    },
    title: {
      type: String,
      default: '',
    },
    isFixedFirstColumn: {
      type: Boolean,
      default: false,
    },
    columns: {
      type: Array,
      default: () => {
        return [];
      },
    },
    rows: {
      type: Array,
      default: () => {
        return [];
      },
    },
    rowClasses: {
      type: [Array, Function],
      default: () => {
        return [];
      },
    },
    pageSize: {
      type: Number,
      default: 10,
    },
    total: {
      type: Number,
      default: 100,
    },
    page: {
      type: Number,
      default: 1,
    },
    hasMore: {
      type: Boolean,
      default: true,
    },
    next: {
      type: String,
      default: '',
    },
    prev: {
      type: String,
      default: '',
    },
    sortable: {
      type: Object,
      default: () => {
        return {
          order: 'id',
          sort: 'desc',
        };
      },
    },
    noDataText: {
      type: String,
      default: 'No data found',
    },
    isStaticMode: {
      type: Boolean,
      default: false,
    },
    isSlotMode: {
      type: Boolean,
      default: false,
    },
    isHidePaging: {
      type: Boolean,
      default: false,
    },
    pageOptions: {
      type: Array,
      default: () => [
        {
          value: 10,
          text: 10,
        },
        {
          value: 25,
          text: 25,
        },
        {
          value: 50,
          text: 50,
        },
      ],
    },
    maxHeight: {
      default: 'auto',
    },
  },
  setup(props, { emit, slots }) {
    let localTable = ref(null);
    const params = useUrlSearchParams('history');

    let defaultPageSize =
      props.pageOptions.length > 0
        ? ref(props.pageOptions[0].value)
        : ref(props.pageSize);
    if (props.pageOptions.length > 0) {
      props.pageOptions.forEach(v => {
        if (
          Object.prototype.hasOwnProperty.call(v, 'value') &&
          Object.prototype.hasOwnProperty.call(v, 'text') &&
          props.pageSize == v.value
        ) {
          defaultPageSize.value = v.value;
        }
      });
    }

    const setting = reactive({
      isSlotMode: props.isSlotMode,

      isCheckAll: false,

      isHidePaging: props.isHidePaging,

      keyColumn: computed(() => {
        let key = '';
        Object.assign(props.columns).forEach(col => {
          if (col.isKey) {
            key = col.field;
          }
        });
        return key;
      }),

      page: props.page,

      pageSize: defaultPageSize.value,

      maxPage: computed(() => {
        if (props.total <= 0) {
          return 0;
        }
        let maxPage = Math.floor(props.total / setting.pageSize);
        let mod = props.total % setting.pageSize;
        if (mod > 0) {
          maxPage++;
        }
        return maxPage;
      }),

      offset: computed(() => {
        return (setting.page - 1) * setting.pageSize + 1;
      }),

      limit: computed(() => {
        let limit = setting.page * setting.pageSize;
        return props.total >= limit ? limit : props.total;
      }),

      paging: computed(() => {
        let startPage = setting.page - 2 <= 0 ? 1 : setting.page - 2;
        if (setting.maxPage - setting.page <= 2) {
          startPage = setting.maxPage - 4;
        }
        startPage = startPage <= 0 ? 1 : startPage;
        let pages = [];
        for (let i = startPage; i <= setting.maxPage; i++) {
          if (pages.length < 5) {
            pages.push(i);
          }
        }
        return pages;
      }),

      order: props.sortable.order,
      sort: props.sortable.sort,
      pageOptions: props.pageOptions,
    });

    const isChecked = ref([]);

    const localRows = computed(() => {
      let rows = props.rows;

      var collator = new Intl.Collator(undefined, {
        numeric: true,
        sensitivity: 'base',
      });
      let sortOrder = setting.sort === 'desc' ? -1 : 1;
      rows.sort(function (a, b) {
        return collator.compare(a[setting.order], b[setting.order]) * sortOrder;
      });

      let result = null;
      result = [];
      for (let index = 0; index < setting.limit; index++) {
        result.push(rows[index]);
      }

      nextTick(function () {
        callIsFinished();
      });

      return result;
    });

    const rowCheckbox = ref([]);
    if (props.hasCheckbox) {
      onBeforeUpdate(() => {
        rowCheckbox.value = [];
      });

      watch(
        () => setting.isCheckAll,
        state => {
          isChecked.value = [];
          if (state) {
            if (props.checkedReturnType == 'row') {
              isChecked.value = props.rows;
            } else {
              props.rows.forEach(val => {
                isChecked.value.push(val[setting.keyColumn]);
              });
            }
          }
          rowCheckbox.value.forEach(val => {
            if (val) {
              val.checked = state;
            }
          });

          emit('return-checked-rows', isChecked.value);
        },
      );
    }

    const checked = (row, event) => {
      event.stopPropagation();
      if (event.target.checked) {
        if (props.checkedReturnType == 'row') {
          isChecked.value.push(row);
        } else {
          isChecked.value.push(row[setting.keyColumn]);
        }
      } else {
        const index = isChecked.value.indexOf(row);
        if (index >= 0) {
          isChecked.value.splice(index, 1);
        }
      }
      if (isChecked.value.length == props.rows.length) {
        setting.isCheckAll = true;
      } else {
        emit('return-checked-rows', isChecked.value);
      }
    };

    const clearChecked = () => {
      isChecked.value = [];
      rowCheckbox.value.forEach(val => {
        if (val && val.checked) {
          val.checked = false;
        }
      });

      emit('return-checked-rows', isChecked.value);
    };

    const doSort = order => {
      let sort = 'asc';
      if (order == setting.order) {
        if (setting.sort == 'asc') {
          sort = 'desc';
        }
      }
      let offset = (setting.page - 1) * setting.pageSize;
      let limit = setting.pageSize;
      setting.order = order;
      setting.sort = sort;
      emit('do-search', offset, limit, order, sort);

      if (setting.isCheckAll) {
        setting.isCheckAll = false;
      } else {
        if (props.hasCheckbox) {
          clearChecked();
        }
      }
    };

    const changePage = (page, prevPage) => {
      setting.isCheckAll = false;
      if (props.hasCheckbox) {
        isChecked.value = [];
      }
      let order = setting.order;
      let sort = setting.sort;
      let offset = (page - 1) * setting.pageSize;
      let limit = setting.pageSize;
      if (!props.isReSearch || page > 1 || page == prevPage) {
        emit('do-search', offset, limit, order, sort);
      }
      params.page = setting.page;
    };

    watch(() => setting.page, changePage);

    watch(
      () => props.page,
      val => {
        if (val <= 1) {
          setting.page = 1;
          emit('get-now-page', setting.page);
        } else if (val >= setting.maxPage) {
          setting.page = setting.maxPage;
          emit('get-now-page', setting.page);
        } else {
          setting.page = val;
        }
      },
    );

    const changePageSize = () => {
      if (setting.page === 1) {
        changePage(setting.page, setting.page);
      } else {
        setting.page = 1;
        setting.isCheckAll = false;
      }
    };

    watch(() => setting.pageSize, changePageSize);

    watch(
      () => props.pageSize,
      newPageSize => {
        setting.pageSize = newPageSize;
      },
    );

    const prevPage = () => {
      if (setting.page == 1) {
        return false;
      }
      setting.page--;
    };

    const movePage = page => {
      setting.page = page;
    };

    const nextPage = () => {
      if (setting.page >= setting.maxPage) {
        return false;
      }
      setting.page++;
    };

    watch(
      () => props.rows,
      () => {
        if (props.isReSearch || props.isStaticMode) {
          setting.page = 1;
        }
        nextTick(function () {
          if (!props.isStaticMode) {
            callIsFinished();
          }
        });
      },
    );

    const callIsFinished = () => {
      if (localTable.value) {
        let localElement =
          localTable.value.getElementsByClassName('is-rows-el');
        emit('is-finished', localElement);
      }
      emit('get-now-page', setting.page);
    };

    const toggleButtonRefs = ref({});

    onMounted(() => {
      nextTick(() => {
        if (props.rows.length > 0) {
          callIsFinished();
        }
      });
    });

    if (props.hasCheckbox) {
      return {
        slots,
        localTable,
        localRows,
        setting,
        rowCheckbox,
        checked,
        doSort,
        prevPage,
        movePage,
        nextPage,
        toggleButtonRefs,
      };
    } else {
      return {
        slots,
        localTable,
        localRows,
        setting,
        checked,
        doSort,
        prevPage,
        movePage,
        nextPage,
        toggleButtonRefs,
      };
    }
  },
});
</script>

<template>
  <div class="vtl vtl-card">
    <h3 class="text-lg" v-if="title">{{ title }}</h3>
    <div class="vtl-card-body">
      <div class="vtl-row">
        <div
          class="col-sm-12"
          :class="{
            'fixed-first-column': isFixedFirstColumn,
            'fixed-first-second-column': isFixedFirstColumn && hasCheckbox,
          }"
        >
          <div v-if="isLoading" class="vtl-loading-mask">
            <div class="vtl-loading-content">
              <span style="color: white">Loading...</span>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table
              class="table"
              ref="localTable"
              :style="'max-height: ' + maxHeight + 'px;'"
            >
              <thead>
                <tr>
                  <th v-if="hasCheckbox">
                    <div>
                      <x-checkbox
                        v-model="setting.isCheckAll"
                        color="primary"
                      />
                    </div>
                  </th>
                  <th
                    v-for="(col, index) in columns"
                    :class="col.headerClasses"
                    :key="index"
                    :style="
                      Object.assign(
                        {
                          width: col.width ? col.width : 'auto',
                        },
                        col.headerStyles,
                      )
                    "
                  >
                    <div
                      :class="{
                        'vtl-sortable': col.sortable,
                        'vtl-both': col.sortable,
                        'vtl-asc':
                          setting.order === col.field && setting.sort === 'asc',
                        'vtl-desc':
                          setting.order === col.field &&
                          setting.sort === 'desc',
                      }"
                      @click.prevent="col.sortable ? doSort(col.field) : false"
                    >
                      {{ col.label }}
                    </div>
                  </th>
                </tr>
              </thead>
              <template v-if="rows.length > 0">
                <tbody>
                  <template v-if="isStaticMode">
                    <tr
                      v-for="(row, i) in localRows"
                      :key="i"
                      class="hover"
                      :class="
                        typeof rowClasses === 'function'
                          ? rowClasses(row)
                          : rowClasses
                      "
                      @click="$emit('row-clicked', row)"
                    >
                      <th v-if="hasCheckbox">
                        <label>
                          <input
                            type="checkbox"
                            class="checkbox checkbox-primary"
                            :ref="
                              el => {
                                rowCheckbox[i] = el;
                              }
                            "
                            :value="row[setting.keyColumn]"
                            @click="checked"
                          />
                        </label>
                      </th>
                      <td
                        v-for="(col, j) in columns"
                        :key="j"
                        :class="col.columnClasses"
                        :style="col.columnStyles"
                      >
                        <div v-if="col.display" v-html="col.display(row)"></div>
                        <template v-else>
                          <div v-if="setting.isSlotMode && slots[col.field]">
                            <slot :name="col.field" :value="row"></slot>
                          </div>
                          <span v-else>
                            {{ row[col.field] }}
                          </span>
                        </template>
                      </td>
                    </tr>
                  </template>
                  <template v-else>
                    <tr
                      v-for="(row, i) in rows"
                      :key="row[setting.keyColumn] ? row[setting.keyColumn] : i"
                      :name="'row-' + i + 1"
                      :class="
                        typeof rowClasses === 'function'
                          ? rowClasses(row)
                          : rowClasses
                      "
                      @click="$emit('row-clicked', row)"
                    >
                      <td v-if="hasCheckbox">
                        <div>
                          <input
                            type="checkbox"
                            class="checkbox"
                            :ref="
                              el => {
                                rowCheckbox.push(el);
                              }
                            "
                            :value="row[setting.keyColumn]"
                            @click.prevent="checked(row, $event)"
                          />
                        </div>
                      </td>
                      <td
                        v-for="(col, j) in columns"
                        :key="j"
                        :class="col.columnClasses"
                        :style="col.columnStyles"
                      >
                        <div v-if="col.display" v-html="col.display(row)"></div>
                        <div v-else>
                          <div v-if="setting.isSlotMode && slots[col.field]">
                            <slot :name="col.field" :value="row"></slot>
                          </div>
                          <span v-else>
                            {{ row[col.field] }}
                          </span>
                        </div>
                      </td>
                    </tr>
                  </template>
                </tbody>
                <tfoot>
                  <tr>
                    <th v-if="hasCheckbox">
                      <div>
                        <x-checkbox
                          v-model="setting.isCheckAll"
                          color="primary"
                        />
                      </div>
                    </th>
                    <th
                      v-for="(col, index) in columns"
                      :class="col.headerClasses"
                      :key="index"
                      :style="
                        Object.assign(
                          {
                            width: col.width ? col.width : 'auto',
                          },
                          col.headerStyles,
                        )
                      "
                    >
                      <div
                        :class="{
                          'vtl-sortable': col.sortable,
                          'vtl-both': col.sortable,
                          'vtl-asc':
                            setting.order === col.field &&
                            setting.sort === 'asc',
                          'vtl-desc':
                            setting.order === col.field &&
                            setting.sort === 'desc',
                        }"
                        @click="col.sortable ? doSort(col.field) : false"
                      >
                        {{ col.label }}
                      </div>
                    </th>
                  </tr>
                </tfoot>
              </template>
            </table>
          </div>
        </div>
      </div>
      <div v-if="rows.length > 0">
        <template v-if="!setting.isHidePaging">
          <div class="flex justify-between items-center gap-2 py-4">
            <x-button
              size="sm"
              icon-left="prev"
              :href="prev"
              :disabled="prev === ''"
              :loading="isLoading"
            >
              Previous
            </x-button>
            <div role="status" aria-live="polite" class="text-sm font-medium">
              Showing {{ setting.offset }} -
              {{ setting.offset + rows.length - 1 }}
            </div>
            <x-button
              size="sm"
              icon-right="next"
              :href="next"
              :disabled="!hasMore"
              :loading="isLoading"
            >
              Next
            </x-button>
          </div>
        </template>
      </div>
      <div v-else>
        <div>
          {{ noDataText }}
        </div>
      </div>
    </div>
  </div>
</template>

<style>
.table :where(th, td) {
  white-space: nowrap;
  padding: 1rem;
  vertical-align: middle;
}

.table tr.active th,
.table tr.active td,
.table tr.active:nth-child(even) th,
.table tr.active:nth-child(even) td {
  @apply bg-primary-200;
}

.table tr.hover:hover th,
.table tr.hover:hover td,
.table tr.hover:nth-child(even):hover th,
.table tr.hover:nth-child(even):hover td {
  @apply bg-primary-200;
}

.table :where(thead, tfoot) :where(th, td) {
  @apply bg-primary-700 text-white text-xs font-bold uppercase text-left border-r;
}

.table :where(tbody th, tbody td) {
  @apply bg-white border;
}

.table-zebra tbody tr:nth-child(even) th,
.table-zebra tbody tr:nth-child(even) td {
  @apply bg-primary-200;
}

:where(.table *:first-child) :where(*:first-child) :where(th, td):first-child {
  border-top-left-radius: 0.5rem;
}

:where(.table *:first-child) :where(*:first-child) :where(th, td):last-child {
  border-top-right-radius: 0.5rem;
}

:where(.table *:last-child) :where(*:last-child) :where(th, td):first-child {
  border-bottom-left-radius: 0.5rem;
}

:where(.table *:last-child) :where(*:last-child) :where(th, td):last-child {
  border-bottom-right-radius: 0.5rem;
}

.vtl-both {
  background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABMAAAATCAQAAADYWf5HAAAAkElEQVQoz7X QMQ5AQBCF4dWQSJxC5wwax1Cq1e7BAdxD5SL+Tq/QCM1oNiJidwox0355mXnG/DrEtIQ6azioNZQxI0ykPhTQIwhCR+BmBYtlK7kLJYwWCcJA9M4qdrZrd8pPjZWPtOqdRQy320YSV17OatFC4euts6z39GYMKRPCTKY9UnPQ6P+GtMRfGtPnBCiqhAeJPmkqAAAAAElFTkSuQmCC');
}

.vtl-sortable {
  cursor: pointer;
  background-position: right;
  background-repeat: no-repeat;
  padding-right: 30px !important;
}

.vtl-asc {
  background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABMAAAATCAYAAAByUDbMAAAAZ0lEQVQ4y2NgGLKgquEuFxBPAGI2ahhWCsS/gDibUoO0gPgxEP8H4ttArEyuQYxAPBdqEAxPBImTY5gjEL9DM+wTENuQahAvEO9DMwiGdwAxOymGJQLxTyD+jgWDxCMZRsEoGAVoAADeemwtPcZI2wAAAABJRU5ErkJggg==);
}

.vtl-desc {
  background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABMAAAATCAYAAAByUDbMAAAAZUlEQVQ4y2NgGAWjYBSggaqGu5FA/BOIv2PBIPFEUgxjB+IdQPwfC94HxLykus4GiD+hGfQOiB3J8SojEE9EM2wuSJzcsFMG4ttQgx4DsRalkZENxL+AuJQaMcsGxBOAmGvopk8AVz1sLZgg0bsAAAAASUVORK5CYII=);
}

.vtl-loading-mask {
  position: absolute;
  z-index: 3;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  flex-flow: column;
  transition: opacity 0.3s ease;
}

.vtl-loading-content {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
